<?php
class Zip extends ZipArchive
{
    public function __construct($nom, $commentaire = '')
    {
        if ($this->open($nom, ZIPARCHIVE::OVERWRITE) !== TRUE) {
            throw new Exception("L'archive ne peut être créée");
        }

        if ($commentaire) {
            $this->setArchiveComment($commentaire);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function addFiles(Array $fichiers)
    {
        foreach ($fichiers as $k => $f) {
            if (!$this->addFile($f)) {
                throw new Exception("Le fichier '$f' n'a pu être ajouté à l'archive");
            }

            if (is_string($k)) {
                $this->setCommentName($f, $k);
            }
        }
    }

    public function addRecursive($chemin, $prefixe = '')
    {
        $chemin = realpath($chemin) . DIRECTORY_SEPARATOR;

        if (!file_exists($chemin)) {
            throw new Exception("Le chemin '$chemin' n'existe pas");
        }

        if (!is_dir($chemin)) {
            throw new Exception("Le chemin '$chemin' existe mais n'est pas un répertoire");
        }

        if (!($dh = opendir($chemin))) {
            throw new Exception("Le répertoire '$chemin' n'est pas accessible");
        }

        while (($fichier = readdir($dh)) !== FALSE) {
            if ($fichier == '.' || $fichier == '..') {
                continue;
            }
            if (is_dir($chemin . $fichier)) {
                $this->addEmptyDir($prefixe . $fichier);
                $this->addRecursive($chemin . $fichier . DIRECTORY_SEPARATOR, $prefixe . $fichier . DIRECTORY_SEPARATOR);
            } else {
                if (!$this->addFile($chemin . $fichier, $prefixe . $fichier)) {
                    throw new Exception("Le fichier '$chemin/$fichier' n'a pu être ajouté à l'archive");
                }
            }
        }
        closedir($dh);
    }
}
?>