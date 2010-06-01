<?php
/**
 * Description of WatOembed
 *
 * @author Cédric Boverie
 */
require_once('OembedProvider.php');

class OembedProvider_Wat extends OembedProvider {

    public function exec() {
        // Récupérer ici les donnnées depuis Wat.tv
        $this->setData('type','video');
        $this->setData('provider_name','Wat.tv');
        $this->setData('provider_url','http://www.wat.tv');

        if(!preg_match('#http://(www\.)?wat.tv/video/.*#i',$this->getUrl()))
                throw new Exception('Unauthorized',401);

        $content = file_get_contents($this->getUrl());
        preg_match("#<meta name=\"video_height\" content=\"([0-9]+)\" />#", $content, $height);
        preg_match("#<meta name=\"video_width\" content=\"([0-9]+)\" />#", $content, $width);
        preg_match("#<link rel=\"video_src\" href=\"(.*)\"/>#", $content, $html);
        preg_match("#<link rel=\"image_src\" href=\"(.*)\" />#", $content, $img_src);
        preg_match("#<meta name=\"name\" content=\"(.*)\" />#", $content, $title);

        $height = $height[1];
        $width = $width[1];
        $html = $html[1];
        $img_src = $img_src[1];
        $title = $title[1];
        $dim = $this->calculerDimension($height,$width);

        $this->setData('title', $title);
        $this->setData('html','<div style="text-align:center;"><object width="'.$dim['width'].'" height="'.$dim['height'].'" type="application/x-shockwave-flash" data="'.$html.'"><param name="movie" value="'.$html.'" /><param name="allowScriptAccess" value="always" /><param name="allowFullScreen" value="true" /></object></div>');

        $this->setData('width',$dim['width']);
        $this->setData('height',$dim['height']);
        $this->setData('thumbnail_url',$img_src);

        $this->setData('thumbnail_height','85');
        $this->setData('thumbnail_width','150');
    }
}
?>
