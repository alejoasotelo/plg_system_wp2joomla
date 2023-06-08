<?php

const PHP_TAB = "\t";

function usage($command)
{
	echo PHP_EOL;
    echo 'Usage: php ' . $command . ' [options]' . PHP_EOL;
	echo PHP_TAB . '[options]:' . PHP_EOL;
	echo PHP_TAB . PHP_TAB . '--from <from>:' . PHP_TAB . 'El tag de la versión desde la cual se van a obtener los commits (ex: `tags/3.8.6`, `4.0-dev`)' . PHP_EOL;
	echo PHP_TAB . PHP_TAB . '--version <version>:' . PHP_TAB . 'La versión para agregar en el changelog' . PHP_EOL;
	echo PHP_TAB . PHP_TAB . '--help:' . PHP_TAB . PHP_TAB . PHP_TAB . 'Show this help output' . PHP_EOL . PHP_EOL;
    echo PHP_TAB . PHP_TAB . 'Ejemplo: php build_changelog.php --from=1.12.0 --version=1.13.0' . PHP_EOL;
	echo PHP_EOL;
}

if (version_compare(PHP_VERSION, '5.4', '<'))
{
	echo "The build script requires PHP 5.4.\n";

	exit(1);
}

$time = time();

// Set path to git binary (e.g., /usr/local/git/bin/git or /usr/bin/git)
ob_start();
passthru('which git', $systemGit);
$systemGit = trim(ob_get_clean());

if (empty($systemGit)) {
    ob_start();
    passthru('where git', $systemGit);
    $systemGit = trim(ob_get_clean());
}

if (empty($systemGit))
{
	die('Install Git');
}

// Make sure file and folder permissions are set correctly
umask(022);

// Shortcut the paths to the repository root and build folder
$repo = dirname(__DIR__);
$here = __DIR__;

// Set paths for the build packages
$tmp      = $here . '/tmp';
$fullpath = $tmp . '/' . $time;

// Parse input options
$options = getopt('', array('help', 'from::', 'version::', 'dest::'));

$from       = $options['from'];
$version       = isset($options['version']) ? $options['version'] : '';
$dest       = isset($options['dest']) ?  $options['dest'] : $repo . '/CHANGELOG.md';
$showHelp     = isset($options['help']);

if ($showHelp)
{
	usage($argv[0]);
	die;
}

if (empty($from)){
	usage($argv[0]);
    die;
}

chdir($repo);
ob_start();
system('"'.$systemGit . '" log --pretty=oneline HEAD...' . $from, $commits);
$commits = explode("\n", trim(ob_get_clean()));

/*
echo "Start build for remote $remote.\n";
echo "Delete old release folder.\n";
system('rm -rf ' . $tmp);
mkdir($tmp);
mkdir($fullpath);
*/
$data = '# Versión'.(!empty($version) ? ' ' . $version : '').' - '.date('d/m/Y');
$data .= "\n\n";

foreach ($commits as &$commit) {

    $parts = explode(' ', $commit);
    unset($parts[0]);
	$commit = implode(' ', array_values($parts));
	
	if (strpos($commit, '[*]') === false && 
		strpos($commit, '[+]') === false && 
		strpos($commit, '[-]') === false && 
		strpos($commit, '[x]') === false) 
	{
		continue;
	}

    $data .= '- ' .  $commit."\n";
}

$filename = $dest;

if (file_exists($filename)) {
	$content = file_get_contents($filename);
	$data .= "\n".$content;
}

file_put_contents($filename, $data);