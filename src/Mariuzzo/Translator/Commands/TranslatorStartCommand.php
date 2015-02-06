<?php namespace Mariuzzo\Translator\Commands;

use Illuminate\Config\Repository as Config;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem as File;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Mariuzzo\Translator\Services\TranslatorService;

/**
 * The TranslatorStartCommand class.
 *
 * @author rmariuzzo <rubens@mariuzzo.com>
 */
class TranslatorStartCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'translator:start';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Start the interactive translator.';

    /**
     * The Laravel file provider.
     * @var Illuminate\Support\Facades\File
     */
    protected $file;

    /**
     * The Laravel config provider.
     * @var Illuminate\Support\Facades\Config
     */
    protected $config;

    /**
     * The translator service.
     * @var Mariuzzo\Translator\Services\TranslatorService
     */
    protected $translator;

    /**
     * Missing translations.
     * @var array
     */
    protected $missing;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(File $file, Config $config)
	{
		parent::__construct();
        $this->file = $file;
        $this->config = $config;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        // Create translator service.
        $this->translator = new TranslatorService($this->getLangFiles());
        $this->translator->setDefaultLocale($this->config->get('app.locale'));

        // Check missing translation.
        $this->missing = $this->flatten($this->translator->getMissing());
        $this->check();

        // Start interactive shell.
        while(true)
        {
            $action = strtoupper($this->ask('What do you want to do? [T]ranslate, [C]heck, [S]ave, [E]xit.'));

            switch ($action) {
                case 'T':
                    $this->translate();
                break;
                case 'C':
                    $this->check();
                break;
                case 'S':
                    $this->save();
                break;
                case 'E':
                    exit;
                break;
            }
        }
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array();
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array();
	}

    /**
     * Return languages files.
     *
     * @return array Array of languages files.
     */
    protected function getLangFiles()
    {
        $messages = array();
        $path = app_path().'/lang';

        if ( ! $this->file->exists($path))
        {
            throw new \Exception("${path} doesn't exists!");
        }

        return $this->file->allFiles($path);
    }

    /**
     * Flatten missing translation keys by locale.
     *
     * @param  array $missing Array of missing translations.
     * @return array          Flattened array of missing translations.
     */
    protected function flatten($missing)
    {
        $flatten = array();

        foreach ($missing as $locale => $bundles)
        {
            foreach ($bundles as $bundle => $keys)
            {
                foreach ($keys as $key)
                {
                    $flatten[$locale][] = array(
                        'bundle'     => $bundle,
                        'key'        => $key,
                        'translated' => false
                    );
                }
            }
        }

        return $flatten;
    }

    /**
     * Interactively translate missing translations.
     *
     * @return void
     */
    protected function translate()
    {
        foreach ($this->missing as $locale => &$keys)
        {
            $count = 0;
            $total = count($keys);

            foreach ($keys as &$key)
            {
                $count++;

                // Skip translated key.
                if ($key['translated'] === true)
                {
                    continue;
                }

                // Get sample value.
                $sampleValue;
                $sampleLocale;

                foreach ($this->translator->getLocales() as $l)
                {
                    $sampleLocale = $l;
                    $sampleValue = $this->translator->get($sampleLocale, $key['bundle'], $key['key']);

                    if ($sampleValue !== null)
                    {
                        break;
                    }
                }

                $name = $key['bundle'] . '.' . $key['key'];
                $this->info('');
                $this->info(" - Translating [$name] into [$locale]. [$count/$total]");

                // TODO: Handle array values.
                if (!is_string($sampleValue))
                {
                    $this->comment(' > Non string value not supported.');
                    continue;
                }

                $this->info(" - Sample [$sampleLocale]: '$sampleValue'");
                $value = $this->ask(' - Translation (left blank to skip): ');
                $value = trim($value);

                // Skip blank values.
                if ($value === '')
                {
                    $this->info(' > Translation skipped!');
                    continue;
                }

                // Save translated key.
                $this->translator->put($locale, $key['bundle'], $key['key'], $value);
                $key['translated'] = true;
                $this->info(' > Translation added!');
            }
        }

        $this->info(' >> No more translation.');
    }

    /**
     * Check translation status.
     *
     * @return void
     */
    protected function check()
    {
        if (count($this->missing) === 0)
        {
            $this->info('Everything is awesome!');
            exit;
        }

        foreach ($this->missing as $locale => $keys)
        {
            $message = ' - ['.$locale.'] is missing: '.count($keys).' translation entries';
            $changes = array_filter($keys, function($key)
            {
                return $key['translated'];
            });
            $message .= ' ('.count($changes).' unsaved changes).';
            $this->info($message);
        }
    }

    /**
     * Save new translations to disk.
     *
     * @return void
     */
    protected function save()
    {
        $this->info('');

        foreach ($this->translator->getTranslations() as $locale => $bundles)
        {
            foreach ($bundles as $bundle => $keys)
            {
                $path = app_path()."/lang/$locale/$bundle.php";
                $contents = "<?php\n\nreturn ".var_export($keys, true).";\n";
                $this->file->put($path, $contents);
                $this->info(" > File saved: $path");
            }
        }

        // Refresh data.
        $this->translator->setFiles($this->getLangFiles());
        $this->missing = $this->flatten($this->translator->getMissing());

        $this->info('');
    }

}
