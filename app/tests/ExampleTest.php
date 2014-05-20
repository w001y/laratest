<?php

class ExampleTest extends TestCase {

	/**
	 * A basic functional test example.
	 *
	 * @return void
	 */
	public function testBasicExample()
	{
		$crawler = $this->client->request('GET', '/api/v1/member/1');
        var_dump($this->client->getResponse()->getContent());
        exit;
		$this->assertTrue($this->client->getResponse()->isOk());
	}

}