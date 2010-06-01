<?php
class UserAgent {

	private $useragent;
	private $browser;
	private $browserVersion;
	private $operatingSystem;
	private $prefixVersion;
	//public $iteration = 0;

        /** Extraire des informations sur le navigateur et l'OS à partir d'un user-agent.
         *
         * @param string $useragent L'user-agent à analyser.
         * @return boolean true
         */
	public function __construct($useragent='') {
		if(!empty($useragent))
			$this->useragent = $useragent;
		else
			$this->useragent = $_SERVER['HTTP_USER_AGENT'];
			
		return true;
	}

	/** Obtenir le nom du navigateur
         *
         * @return string Le nom du navigateur
         */
	public function getBrowser() {
		
		if(empty($this->browser)) // Pas 2 fois le même boulot
		{

			if($this->verif('Navigator'))
			{
				$browser = 'Netscape';
				$this->prefixVersion = 'Navigator/';
			}
			else if($this->verif('Netscape'))
			{
				$browser = 'Netscape';
				$this->prefixVersion []= 'Netscape6/';
				$this->prefixVersion []= 'Netscape/';
			}
			else if($this->verif('SeaMonkey'))
			{
				$browser = 'SeaMonkey';
				$this->prefixVersion = 'SeaMonkey/';
			}
			else if($this->verif('Iceweasel'))
			{
				$browser = 'Iceweasel';
				$this->prefixVersion = 'Iceweasel/';
			}
			else if($this->verif('IceCat'))
			{
				$browser = 'IceCat';
				$this->prefixVersion = 'IceCat/';
			}
			else if($this->verif('Flock'))
			{
				$browser = 'Flock';
				$this->prefixVersion = 'Flock/';
			}
			else if($this->verif('Camino'))
			{
				$browser = 'Camino';
				$this->prefixVersion = 'Camino/';
			}
			else if($this->verif('Firefox'))
			{
				$browser = 'Mozilla Firefox';
				$this->prefixVersion = 'Firefox/';
			}
			
			else if($this->verif('Chrome'))
			{
				$browser = 'Chrome';
				$this->prefixVersion = 'Chrome/';
			}
			else if($this->verif('Safari'))
			{
				$browser = 'Safari';
				$this->prefixVersion []= 'Version/';
				$this->prefixVersion []= 'Safari/';
			}
			
			else if($this->verif('AOL') || $this->verif('America Online Browser'))
			{
				$browser = 'AOL Browser';
				$this->prefixVersion []= 'AOL ';
				$this->prefixVersion []= 'America Online Browser ';
			}
			
			else if($this->verif('Opera'))
			{
				$browser = 'Opera';
				$this->prefixVersion []= 'Version/';
				$this->prefixVersion []= 'Opera/';
				$this->prefixVersion []= 'Opera ';
			}
			else if($this->verif('MSIE'))
			{
				$browser = 'Internet Explorer';
				$this->prefixVersion = 'MSIE ';
			}
			
			else if($this->verif('Iceape'))
			{
				$browser = 'Iceape';
				$this->prefixVersion = 'Iceape/';
			}

			else if($this->verif('Konqueror'))
			{
				$browser = 'Konqueror';
				$this->prefixVersion = 'Konqueror/';
			}

			else if($this->verif('PlayStation Portable'))
			{
				$browser = 'PSP Browser';
				$this->prefixVersion = '; ';
			}
			else if($this->verif('PLAYSTATION'))
			{
				$browser = 'PlayStation Browser';
				$this->prefixVersion = '; ';
			}

			else if($this->verif('Thunderbird'))
			{
				$browser = 'Thunderbird';
				$this->prefixVersion = 'Thunderbird/';
			}

			else if($this->verif('Googlebot-Image'))
			{
				$browser = 'Googlebot Image';
				$this->prefixVersion = 'Googlebot-Image/';
			}
			else if($this->verif('Googlebot'))
			{
				$browser = 'Googlebot';
				$this->prefixVersion = 'Googlebot/';
			}			
			else if($this->verif('FeedFetcher-Google'))
			{
				$browser = 'FeedFetcher-Google';
			}
			else if($this->verif('Yahoo'))
			{
				$browser = 'Yahoo! Slurp';
			}
                        else if($this->verif('msnbot'))
			{
				$browser = 'MsnBot';
				$this->prefixVersion = '/';
			}
                        else if($this->verif('Ask Jeeves'))
			{
				$browser = 'Ask Jeeves';
				$this->prefixVersion = 'Mozilla/';
			}
			else if($this->verif('W3C_Validator'))
			{
				$browser = 'W3C Validator';
				$this->prefixVersion = 'W3C_Validator/';
			}
			else if($this->verif('Gregarius'))
			{
				$browser = 'Gregarius';
				$this->prefixVersion = 'Gregarius/';
			}
			else if($this->verif('Wget'))
			{
				$browser = 'Wget';
				$this->prefixVersion = 'Wget/';
			}
			else if($this->verif('Lynx'))
			{
				$browser = 'Lynx';
				$this->prefixVersion = 'Lynx/';
			}
                        
			else if($this->verif('Mozilla'))
			{
				$browser = 'Mozilla';
				$this->prefixVersion []= 'rv:';
				$this->prefixVersion []= 'Mozilla/';
			}

			$this->browser = $browser;
		}
		return $this->browser;
	}

        /** Obtenir la version du navigateur
         *
         * @return string La version du navigateur
         */
	public function getBrowserVersion() {
	
		if(empty($this->browserVersion)) // Pas deux fois le boulot
		{
			if(empty($this->prefixVersion)) // Pas de pr�fixe définit, la fonction ne peut pas agir
				return false;
		
			if(is_array($this->prefixVersion))
			{
				$version = '';
				$i = 0;
				while($version == '' AND isset($this->prefixVersion[$i]))
				{
					preg_match('#'.preg_quote($this->prefixVersion[$i]).'([a-zA-Z0-9\.]+)#', $this->useragent, $version);
					$version = $version[1];
					$i++;
				}
			}
			else
			{
				preg_match('#'.preg_quote($this->prefixVersion).'([a-zA-Z0-9\.]+)#', $this->useragent, $version);
				$version = $version[1];
			}
			
			if($this->browser == 'Safari')
				$version = $this->webkit2safari($version);
			
			if(empty($version)) // Version vide si on a pas trouvé mieux
				$version = '';
			
			$this->browserVersion = $version;
		}
		return $this->browserVersion;
	}
	
	/** Obtenir le système d'exploitation
         *
         * @return string Le système d'exploitation
         */
	public function getOS() {
		
		if(empty($this->operatingSystem)) // Pas deux fois le boulot
		{
			if($this->verif('Windows NT 6.1'))
				$os = 'Windows 7';
			else if($this->verif('Windows NT 6'))
				$os = 'Windows Vista';
			else if($this->verif('Windows NT 5.1'))
				$os = 'Windows XP';
			else if($this->verif('Windows NT 5.0'))
				$os = 'Windows 2000';
			else if($this->verif('Windows NT'))
				$os = 'Windows NT';
                        else if($this->verif('Windows ME'))
				$os = 'Windows ME';
			else if($this->verif('Windows 98') || $this->verif('Win98'))
				$os = 'Windows 98';
			else if($this->verif('Windows 95'))
				$os = 'Windows 95';
			else if($this->verif('Windows'))
				$os = 'Windows';
			else if($this->verif('iPod') || $this->verif('iPhone'))
				$os = 'Mac OS X Mobile';
			else if($this->verif('Mac OS X'))
				$os = 'Mac OS X';
			else if($this->verif('Mac_PowerPC'))
				$os = 'Mac OS 9';
			else if($this->verif('Debian'))
				$os = 'Debian Linux';
			else if($this->verif('Ubuntu'))
				$os = 'Ubuntu Linux';
			else if($this->verif('Fedora'))
				$os = 'Fedora Linux';
			else if($this->verif('Red Hat'))
				$os = 'Red Hat Linux';
			else if($this->verif('Linux'))
				$os = 'Linux';
			else if($this->verif('PLAYSTATION 3'))
				$os = 'PlayStation 3';
				
				
				$this->operatingSystem = $os;
		}
	
		return $this->operatingSystem;
	}

        /** Récupérer l'user-agent utilisé
         *
         * @return string L'user-agent
         */
	public function getUserAgent() {
		return $this->useragent;
	}

        /** Permet d'obtenir la version de Safari à partir de la version du moteur de rendu Webkit
         *
         * @param string $webkit La version de Webkit
         * @return string La version de Safari correpondante
         */
	private function webkit2safari($webkit) {
	
		switch($webkit) {
			case '48':
			return '0.8';
			break;
			case '73':
			return '0.9';
			break;
			case '85':
			return '1.0';
			break;
			case '85.8.5':
			return '1.0.3';
			break;
			case '100':
			return '1.1';
			break;
			case '125':
			return '1.2';
			break;
			case '312':
			return '1.3';
			break;
			case '312.3':
			return '1.3.1';
			break;
			case '312.5':
			return '1.3.2';
			break;
			case '312.6':
			return '1.3.2';
			break;
			case '412':
			return '2.0';
			break;
			case '416.11':
			return '2.0.2';
			break;
			case '419.3':
			return '2.0.4';
			break;
			default:
			return $webkit;		
		}
	}

        /** Teste la présence de $word dans l'user-agent
         *
         * @param string $word Tester la présence de cette chaine dans l'UA
         * @return boolean Présence de $word dans l'user-agent
         */
	private function verif($word) {
		//$this->iteration++;
		if(stripos($this->useragent, $word) !== FALSE)
			return true;
		else
			return false;
	}

}
/*
<form action="" method="post">
UA : <input type="text" name="useragent" value="<?php echo $_POST['useragent']; ?>" size="120" /> 
<input type="submit" value="Check" />
</form>
<?php
$ua = new UserAgent($_POST['useragent']);
echo 'Navigateur : '.$ua->getBrowser().' '.$ua->getBrowserVersion();
echo '<br />OS : ';
echo $ua->getOS();

echo '<pre>';
print_r($ua);
echo '</pre>';

?>
*/
?>