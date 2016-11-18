<?php

namespace acfunpro\afcloudstorage;

/**
 *  Client of cloud storage
 */
class CCloudStorage
{
	public function __construct()
	{
	}
	public function __destruct()
	{
	}

	//
	//	set domains for Cross-Origin Resource Sharing
	//
	public function GetDomains()
	{
		return true;
	}
}