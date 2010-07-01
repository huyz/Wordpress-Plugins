<?php
class Shortlinks {
	
	private $http;
	private $login;
	private $password;
	
	function __construct($service='') {
		if(!class_exists('WP_Http'))
			include_once( ABSPATH . WPINC. '/class-http.php' );			
		$this->http = new WP_Http;
	}
	
	function setApi($login,$password) {
		$this->login = $login;
		$this->password = $password;
	}
	
	function getLink($url,$service='') {
		if(method_exists($this,$service))
			return $this->$service(urlencode($url));
		else
			return false;
	}
	
	function getSupportedServices() {
		return array(
			'isgd' => 'is.gd',
			'bitly' => 'bit.ly',
			'googl' => 'goo.gl',
			'tinyurl' => 'tinyurl.com',
			'supr' => 'su.pr',
			'cligs' => 'cli.gs',
			'twurlnl' => 'twurl.nl',
			'fongs' => 'fon.gs',
		);
	}

	/* Services function */
	
	function isgd($url) {
		$result = $this->http->request('http://is.gd/api.php?longurl='.$url);
		if(!is_wp_error($result) && $result['response']['code'] == 200)
			return $result['body'];
		else
			return false;
	}
	
	function tinyurl($url) {
		$result = $this->http->request('http://tinyurl.com/api-create.php?url='.$url);
		if(!is_wp_error($result) && $result['response']['code'] == 200)
			return $result['body'];
		else
			return false;
	}
	
	function supr($url) {
		$result = $this->http->request('http://su.pr/api/simpleshorten?version=1.0&url='.$url);
		if(!is_wp_error($result) && $result['response']['code'] == 200)
			return $result['body'];
		else
			return false;
	}
		
	function cligs($url) {
		$result = $this->http->request('http://cli.gs/api/v1/cligs/create?url='.$url);
		if(!is_wp_error($result) && $result['response']['code'] == 200)
			return $result['body'];
		else
			return false;
	}
	
	function fongs($url) {
		$result = $this->http->request('http://fon.gs/create.php?url='.$url);
		if(!is_wp_error($result) && $result['response']['code'] == 200)
			return trim(strstr($result['body'],' '));
		else
			return false;
	}

	function twurlnl($url) {
		$body = array('link' => array('url' => urldecode($url)));
		$result = $this->http->request('http://tweetburner.com/links', array( 'method' => 'POST', 'body' => $body) );
		if(!is_wp_error($result) && $result['response']['code'] == 200)
			return $result['body'];
		else
			return false;
	}
	
	function bitly($url) {
		if(empty($this->login) || empty($this->password))
			return false;
		
		$result = $this->http->request('http://api.bit.ly/v3/shorten?login='.$this->login.'&apiKey='.$this->password.'&longUrl='.$url.'&format=json');
		if(!is_wp_error($result) && $result['response']['code'] == 200)
		{
			$content = json_decode($result['body'],true);
			if($content['status_code'] == 200)
				return $content['data']['url'];
			else
				return false;
		}
		else
			return false;		
	}
	
	function googl($url) {
		$result = $this->http->request('http://ggl-shortener.appspot.com/?url='.$url);
		if(!is_wp_error($result) && $result['response']['code'] == 200)
		{
			$content = json_decode($result['body'],true);
			return $content['short_url'];
		}
		else
			return false;		
	}

	/* Helpers */
	
	
}