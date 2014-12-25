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
        while(true) {
            $action = strtoupper($this->ask('What do you want to do? [T]ranslate, [C]heck, [S]ave, [E]xit.'));

            if ($action === 'T') {
                $this->translate();
            }

            if ($action === 'C') {
                $this->check();
            }

            if ($action === 'S') {
                $this->save();
            }

            if ($action === 'E') {
                exit;
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
    protected function flatten($missing) {
        $flatten = array();
        foreach ($missing as $locale => $source) {
            foreach ($source as $key => $lines) {
                foreach ($lines as $line) {
                    $flatten[$locale][] = array(
                        'key'        => $key,
                        'line'       => $line,
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
        foreach ($this->missing as $locale => &$source) {

            $count = 0;
            $total = count($source);

            foreach ($source as &$message) {
                $count++;

                // Skip already translated messages.
                if ($message['translated'] === true) continue;

                // Get existing sample.
                $sampleValue;
                $sampleLocale;
                foreach ($this->translator->getLocales() as $l) {
                    $sampleValue = $this->translator->get($l, $message['key'], $message['line']);
                    if ($sampleValue !== null) {
                        $sampleLocale = $l;
                        break;
                    }
                }

                $key = $message['key'] . '.' . $message['line'];
                $this->info('');
                $this->info(" - Translating [$key] into [$locale]. [$count/$total]");

                // TODO: Handle array values.
                if (!is_string($sampleValue)) {
                    $this->comment(' > Non string value not supported.');
                    continue;
                }

                $this->info(" - Sample [$sampleLocale]: '$sampleValue'");

                $value = $this->ask(' - Translation (left blank to skip): ');
                $value = trim($value);

                // Skipe blank values.
                if ($value === '') {
                    $this->info(' > Translation skipped!');
                } else {
                    $this->translator->put($locale, $message['key'], $message['line'], $value);
                    $message['translated'] = true;
                    $this->info(' > Translation added!');
                }
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
        if (count($this->missing) === 0) {
            $this->info('Everything is awesome!');
            exit;
        }

        foreach ($this->missing as $locale => $source) {
            $message = ' - ['.$locale.'] is missing: '.count($source).' translation entries';
            $changes = array_filter($source, function($s) {
                return $s['translated'];
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

        foreach ($this->translator->getSource() as $locale => $source) {
            foreach ($source as $key => $message) {
                $contents = $output = "<?php\n\nreturn ".var_export($message['lines'], true).";\n";
                if (!isset($message['path'])) {
                    $message['path'] = app_path()."/lang/$locale/$key.php";
                }
                $this->file->put($message['path'], $contents);
                $this->info(' > File saved: '.$message['path']);
            }
        }

        // Refresh data.
        $this->translator->setFiles($this->getLangFiles());
        $this->missing = $this->flatten($this->translator->getMissing());

        $this->info('');
    }

}
