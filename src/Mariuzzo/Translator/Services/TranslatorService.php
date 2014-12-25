<?php namespace Mariuzzo\Translator\Services;

class TranslatorService {

    protected $source;
    protected $locales;
    protected $defaultLocale;
    protected $missing;

    public function __construct($files) {
        $this->setFiles($files);
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }

    public function getMissing()
    {
        return $this->missing;
    }

    public function setFiles($files)
    {
        $this->parse($files);
        $this->locales = array_keys($this->source);
        $this->check();
    }

    protected function parse($files)
    {
        foreach ($files as $file) {
            // Only parse PHP files.
            $pathName = $file->getRelativePathName();
            if ( pathinfo($pathName)['extension'] !== 'php' ) continue;

            $locale = $file->getRelativePath();
            $key = basename($pathName, '.php');

            // Parse languages into array.
            $this->source[$locale][$key] = array(
                'path'   => $file->getPathname(),
                'locale' => $locale,
                'lines'  => include $file->getPathname()
            );
        }
    }

    protected function check()
    {
        $this->missing = array();

        foreach ($this->source as $asource) {
            foreach ($asource as $akey => $amessage) {
                foreach (array_keys($amessage['lines']) as $key) {
                    foreach ($this->source as $blocale => $bsource) {
                        if (!isset($bsource[$akey]['lines'][$key])) {
                            $this->missing[$blocale][$akey][] = $key;
                        }
                    }
                }
            }
        }

        foreach ($this->missing as $locale => $source) {
            foreach ($source as $key => $lines) {
                $this->missing[$locale][$key] = array_unique($this->missing[$locale][$key]);
            }
        }
    }

    public function get($locale, $key, $line)
    {
        if (!isset($this->source[$locale])) {
            return null;
        }
        if (!isset($this->source[$locale][$key])) {
            return null;
        }
        if (!isset($this->source[$locale][$key]['lines'][$line])) {
            return null;
        }
        return $this->source[$locale][$key]['lines'][$line];
    }

    public function put($locale, $key, $line, $value)
    {
        $this->source[$locale][$key]['lines'][$line] = $value;
    }

}
