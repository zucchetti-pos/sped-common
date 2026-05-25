<?php

namespace NFePHP\Common\Tests\Certificate;

use NFePHP\Common\Certificate;

class CertificateTest extends \PHPUnit\Framework\TestCase
{
    const TEST_PFX_FILE = '/../fixtures/certs/certificado_teste.pfx';
    const TEST_PRIVATE_KEY = '/../fixtures/certs/x99999090910270_priKEY.pem';
    const TEST_PUBLIC_KEY = '/../fixtures/certs/x99999090910270_pubKEY.pem';
    const TEST_CHAIN_KEYS = '/../fixtures/certs/chain.pem';
    const TEST_EXPECTED_CHAIN = '/../fixtures/certs/certwithchain.pem';
    const TEST_EXPECTED_PFX = '/../fixtures/certs/expected.pfx';
    const TEST_EXPECTED_PFX1 = '/../fixtures/certs/expected1.pfx';

    public function testShouldLoadPfxCertificate()
    {
        $certificate = Certificate::readPfx(file_get_contents(__DIR__ . self::TEST_PFX_FILE), 'associacao');
        $this->assertEquals('NFe - Associacao NF-e:99999090910270', $certificate->getCompanyName());
        $utc = new \DateTimeZone('UTC');
        $this->assertEquals(new \DateTime('2009-05-22 17:07:03', $utc), $certificate->getValidFrom());
        $this->assertEquals(new \DateTime('2010-10-02 17:07:03', $utc), $certificate->getValidTo());
        $this->assertTrue($certificate->isExpired());
        $dataSigned = $certificate->sign('nfe');
        $this->assertTrue($certificate->verify('nfe', $dataSigned));
    }

    public function testShouldLoadCertificate()
    {
        $certificate = new Certificate(
            new Certificate\PrivateKey(file_get_contents(__DIR__ . self::TEST_PRIVATE_KEY)),
            new Certificate\PublicKey(file_get_contents(__DIR__ . self::TEST_PUBLIC_KEY)),
            new Certificate\CertificationChain()
        );
        $this->assertInstanceOf(Certificate::class, $certificate);
        $this->assertEquals('NFe - Associacao NF-e:99999090910270', $certificate->getCompanyName());
        $utc = new \DateTimeZone('UTC');
        $this->assertEquals(new \DateTime('2009-05-22 17:07:03', $utc), $certificate->getValidFrom());
        $this->assertEquals(new \DateTime('2010-10-02 17:07:03', $utc), $certificate->getValidTo());
        $this->assertTrue($certificate->isExpired());
        $dataSigned = $certificate->sign('nfe');
        $this->assertTrue($certificate->verify('nfe', $dataSigned));
    }

    /**
     * Garante que getValidFrom()/getValidTo() devolvem o MESMO instante absoluto
     * gravado no PFX, independentemente do timezone default do PHP.
     *
     * O arquivo PFX guarda as datas em UTC ("...Z"). O openssl_x509_parse expõe esse
     * instante via validFrom_time_t/validTo_time_t (epoch UTC), que é a fonte de verdade.
     * Antes da correção, createFromFormat ignorava o "Z" e usava a tz default do PHP,
     * fazendo o instante absoluto variar conforme o servidor.
     */
    public function testGetValidFromAndValidToReturnSameInstantAsPfxRegardlessOfDefaultTimezone()
    {
        $pfxContent = file_get_contents(__DIR__ . self::TEST_PFX_FILE);

        // Fonte de verdade: lê direto do PFX via openssl.
        openssl_pkcs12_read($pfxContent, $certs, 'associacao');
        $detail = openssl_x509_parse($certs['cert']);

        // Sanity check: o PFX de teste declara estas datas em UTC.
        $this->assertSame('090522170703Z', $detail['validFrom']);
        $this->assertSame('101002170703Z', $detail['validTo']);
        $this->assertSame(1243012023, $detail['validFrom_time_t']); // 2009-05-22 17:07:03 UTC
        $this->assertSame(1286039223, $detail['validTo_time_t']);   // 2010-10-02 17:07:03 UTC

        $originalTz = date_default_timezone_get();
        try {
            // Simula um servidor em São Paulo (UTC-3) — onde o bug aparecia.
            date_default_timezone_set('America/Sao_Paulo');

            $certificate = Certificate::readPfx($pfxContent, 'associacao');

            // O instante absoluto retornado deve bater com o epoch do PFX.
            $this->assertSame(
                $detail['validFrom_time_t'],
                $certificate->getValidFrom()->getTimestamp(),
                'getValidFrom() deve devolver o mesmo instante absoluto gravado no PFX'
            );
            $this->assertSame(
                $detail['validTo_time_t'],
                $certificate->getValidTo()->getTimestamp(),
                'getValidTo() deve devolver o mesmo instante absoluto gravado no PFX'
            );

            // E, em UTC, deve formatar exatamente como o "Z" do PFX.
            $this->assertSame(
                '2009-05-22 17:07:03',
                $certificate->getValidFrom()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')
            );
            $this->assertSame(
                '2010-10-02 17:07:03',
                $certificate->getValidTo()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')
            );
        } finally {
            date_default_timezone_set($originalTz);
        }
    }

    public function testShouldGetExceptionWhenLoadPfxCertificate()
    {
        $this->expectException(\NFePHP\Common\Exception\CertificateException::class);
        Certificate::readPfx(file_get_contents(__DIR__ . self::TEST_PFX_FILE), 'error');
    }

    public function testShouldLoadChainCertificates()
    {
        $certificate = new Certificate(
            new Certificate\PrivateKey(file_get_contents(__DIR__ . self::TEST_PRIVATE_KEY)),
            new Certificate\PublicKey(file_get_contents(__DIR__ . self::TEST_PUBLIC_KEY)),
            new Certificate\CertificationChain(file_get_contents(__DIR__ . self::TEST_CHAIN_KEYS))
        );
        $expected = file_get_contents(__DIR__ . self::TEST_EXPECTED_CHAIN);
        $actual = "{$certificate}";
        $this->assertEquals($expected, $actual);
    }
}
