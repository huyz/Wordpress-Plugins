<?php
$providers = array('Wat');
if(in_array($_GET['provider'],$providers))
{
    require_once(dirname(__FILE__).'/OembedProvider.php');
    OembedProvider::load($_GET['provider'],$_GET['url'],$_GET['format']);
}