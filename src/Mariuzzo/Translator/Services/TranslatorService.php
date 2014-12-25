<?php namespace Mariuzzo\Translator\Services;

/**
 * The TranslatorService class.
 *
 * @author rmariuzzo <rubens@mariuzzo.com>
 */
class TranslatorService {

    /**
     * The translations source.
     *
     * @var array
     */
    protected $source;

    /**
     * All available locales.
     *
     * @var array
     */
    protected $locales;

    /**
     * The default locale.
     *
     * @var string
     */
    protected $defaultLocale;

    /**
     * Missing translations.
     *
     * @var array
     */
    protected $missing;

    /**
     * Construct a new TranslatorService.
     *
     * @param array $files Array of language files.
     */
    public function __construct($files) {
        $this->setFiles($files);
    }

    /**
     * Return translations source.
     *
     * @return array Translations source.
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Return all available locales.
     *
     * @return array Available locales.
     */
    public function getLocales()
    {
        return $this->locales;
    }

    /**
     * Return the default locale.
     *
     * @return string The default locale.
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Set the default locale.
     *
     * @param string $locale A locale.
     */
    public function setDefaultLocale($locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * Return missing translations.
     *
     * @return array Missing translations.
     */
    public function getMissing()
    {
        return $this->missing;
    }

    /**
     * Set languages files.
     *
     * @param array $files Languages files.
     */
    public function setFiles($files)
    {
        $this->parse($files);
        $this->locales = array_keys($this->source);
        $this->check();
    }

    /**
     * Parse languages files.
     *
     * @param  array $files Language files.
     *
     * @return void
     */
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

    /**
     * Check for missing translations.
     *
     * @return void
     */
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

    /**
     * Return a translation message.
     *
     * @param  string $locale A locale.
     * @param  string $key    A key.
     * @param  string $line   A line.
     *
     * @return string         A translation message.
     */
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

    /**
     * Put a translation.
     *
     * @param  string $locale The locale.
     * @param  string $key    The key.
     * @param  string $line   The line.
     * @param  string $value  The translation value.
     *
     * @return void
     */
    public function put($locale, $key, $line, $value)
    {
        $this->source[$locale][$key]['lines'][$line] = $value;
    }

}
