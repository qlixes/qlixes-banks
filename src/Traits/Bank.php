<?php

namespace qlixes\Bank\Traits;

trait Bank
{
	function getPort($port)
	{
		return ($port)  ?? 443;
	}

 	/**
     * Build the ddn domain.
     * output = 'https://sandbox.bca.co.id:443'
     * scheme = http(s)
     * host = sandbox.bca.co.id
     * port = 80 ? 443
     *
     * @return string
     */
    private function ddnDomain($settings = [])
    {
        return $settings['scheme'] . '://' . $settings['host'] . ':' . $settings['port'] . '/';
    }
}