<?php
/**
 * Description of OembedProvider
 *
 * @author Cédric Boverie
 */
abstract class OembedProvider {
    
    private $url;
    private $data = array();

    public function __construct($url) {
        
        if(empty($url))
            throw new Exception('Not Found',404);
        
        $this->url = urldecode($url);
        $this->setData('version','1.0');
        $this->exec();
    }

    protected function setData($name,$value) {
        $this->data[$name] = $value;
    }

    protected function getData($name) {
        if(isset($this->data[$name]))
            return $this->data[$name];
        else
            return '';
    }

    protected function getUrl() {
        return $this->url;
    }

    abstract public function exec();

    public function calculerDimension($height,$width) {
        // Récupération des dimensions maximales
        $maxwidth = intval($_GET['maxwidth']);
        $maxheight = intval($_GET['maxheight']);

        // Calcul des proportions
        if($maxwidth != 0 && $width > $maxwidth) {
            $ratio = $maxwidth/$width;
            $width = round($width*$ratio);
            $height = round($height*$ratio);
        }
        if($maxheight != 0 && $height > $maxheight) {
            $ratio = $maxheight/$height;
            $width = round($width*$ratio);
            $height = round($height*$ratio);
        }
        return array('height' => $height,'width' => $width);
    }

    public function output($format='') {
        if(empty($format)) // Si aucun format, json par défaut
            $format = 'json';
        if($format != 'xml' && $format != 'json') // json et xml, les formats autorisés
            throw new Exception('Not Implemented',501);

        // Vérifications par rapport aux spécs oEmbed
        if(isset($this->data['type']))
        {
            if($this->data['type'] == 'video' || $this->data['type'] == 'rich') // Vidéo ou rich
            {
                if(!isset($this->data['html']) || !isset($this->data['width']) || !isset($this->data['height']))
                    throw new Exception('Unauthorized',401);
            }
            else if($this->data['type'] == 'photo') // Photo
            {
                if(!isset($this->data['url']) || !isset($this->data['width']) || !isset($this->data['height']))
                    throw new Exception('Unauthorized',401);
            }
            else if($this->data['type'] != 'link')
            {
                throw new Exception('Unauthorized',401);
            }
            
        }
        else // Aucun type sélectionné
            throw new Exception('Unauthorized',401);

        $this->setData('format',$format);
        if($format == 'json')
        {
            header('Content-Type: application/json');
            //header('Content-Type: application/json+oembed');
            echo json_encode($this->data,true);
        }
        else if($format == 'xml')
        {
            header('Content-Type: text/xml');
            //header('Content-Type: text/xml+oembed');
            echo '<?xml version="1.0" encoding="utf-8" standalone="yes"?>';
            echo '<oembed>';
            foreach($this->data as $attr => $val)
            {
                    echo '<'.$attr.'>';
                    echo $val;
                    echo '</'.$attr.'>';
            }
            echo '</oembed>';
        }
    }

    static function load($provider,$url,$format='') {
        try {
            $provider = 'OembedProvider_'.$provider; // Namespace
            // On vérifie l'existence de la classe spécialisée
            if(!@include_once(dirname(__FILE__).'/'.$provider.'.php'))
                die('Provider not found !');
            $wat = new $provider($url);
            $wat->output($format);
        } catch(Exception $e) {
            header('x', true, $e->getCode());
            die($e->getCode().' '.$e->getMessage());
        }
    }
}
?>
