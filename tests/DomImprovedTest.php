<?php

namespace NFePHP\Common\Tests;

use NFePHP\Common\DOMImproved;

class DomImprovedTest extends \PHPUnit\Framework\TestCase
{
    public function testInstanciate()
    {
        $dom = new DOMImproved();
        $this->assertInstanceOf(DOMImproved::class, $dom);
    }

    public function testAddArrayChild()
    {
        $this->assertTrue(true);
    }
    public function testAddChild()
    {
        $this->assertTrue(true);
    }
    public function testAppChild()
    {
        $this->assertTrue(true);
    }
    public function testAppChildBefore()
    {
        $this->assertTrue(true);
    }
    public function testAppExternalChild()
    {
        $this->assertTrue(true);
    }
    public function testAppExternalChildBefore()
    {
        $this->assertTrue(true);
    }
    public function testGetChaveWithAlphanumericCnpj()
    {
        $xml = '<NFe xmlns="http://www.portalfiscal.inf.br/nfe">'
            . '<infNFe Id="NFe520604VL1J4ZDW000163550120000007801267301615" versao="4.00"></infNFe>'
            . '</NFe>';
        $dom = new DOMImproved();
        $dom->loadXMLString($xml);
        $this->assertEquals(
            '520604VL1J4ZDW000163550120000007801267301615',
            $dom->getChave()
        );
    }
    public function testGetNode()
    {
        $this->assertTrue(true);
    }
    public function testGetNodeValue()
    {
        $this->assertTrue(true);
    }
    public function testGetValue()
    {
        $this->assertTrue(true);
    }
    public function testLoadXMLFile()
    {
        $this->assertTrue(true);
    }
    public function testLoadXMLString()
    {
        $this->assertTrue(true);
    }
}
