<?php namespace Mariuzzo\Translator\Services;

/**
 * The TranslatorService class.
 *
 * @author rmariuzzo <rubens@mariuzzo.com>
 */
class TranslatorService {

    /**
     * The translations.
     *
     * @var array
     */
    protected $translations;

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
    public function __construct($files)
    {
        $this->setFiles($files);
    }

    /**
     * Return translations.
     *
     * @return array Translations.
     */
    public function getTranslations()
    {
        return $this->translations;
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
        $this->locales = array_keys($this->translations);
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
        foreach ($files as $file)
        {
            // Filter PHP files.
            $pathName = $file->getRelativePathName();

            if ( pathinfo($pathName)['extension'] !== 'php' )
            {
                continue;
            }

            $locale = $file->getRelativePath();
            $bundle = basename($pathName, '.php');
            $keys = include $file->getPathname();

            $this->translations[$locale][$bundle] = $keys;
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

        // Iterate all translation keys.
        foreach ($this->translations as $bundles_a)
        {
            foreach ($bundles_a as $bundle_a => $keys_a)
            {
                $keys_a = array_keys($keys_a);

                // Compare each key against all translations.
                foreach ($keys_a as $key_a)
                {
                    foreach ($this->translations as $locale_b => $bundles_b)
                    {

                        // Check missing keys.
                        if (!isset($bundles_b[$bundle_a][$key_a]))
                        {
                            $this->missing[$locale_b][$bundle_a][] = $key_a;
                        }
                    }
                }
            }
        }

        // Remove duplicated keys.
        foreach ($this->missing as $locale => $bundles)
        {
            foreach ($bundles as $bundle => $entries)
            {
                $this->missing[$locale][$bundle] = array_unique($this->missing[$locale][$bundle]);
            }
        }
    }

    /**
     * Return a translation message.
     *
     * @param  string $locale The locale.
     * @param  string $bundle The bundle.
     * @param  string $key    The key.
     *
     * @return string         A translation message.
     */
    public function get($locale, $bundle, $key)
    {
        if (!isset($this->translations[$locale]))
        {
            return null;
        }

        if (!isset($this->translations[$locale][$bundle]))
        {
            return null;
        }

        if (!isset($this->translations[$locale][$bundle][$key]))
        {
            return null;
        }

        return $this->translations[$locale][$bundle][$key];
    }

    /**
     * Put a translation.
     *
     * @param  string $locale The locale.
     * @param  string $bundle The bundle.
     * @param  string $key    The key.
     * @param  string $value  The translation value.
     *
     * @return void
     */
    public function put($locale, $bundle, $key, $value)
    {
        $this->translations[$locale][$bundle][$key] = $value;
    }

}
