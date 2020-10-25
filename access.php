<?php
    use PhpSchool\CliMenu\CliMenu;
    use PhpSchool\CliMenu\Builder\CliMenuBuilder;

    (php_sapi_name() === 'cli' && stripos(PHP_OS, 'winnt')===false) or die("cli on *nix only");
    require_once __DIR__ . '/vendor/autoload.php';
    spl_autoload_register(function ($class) {
        $class = explode('\\', $class);
        $class = array_pop($class);
        require_once __DIR__ . "/{$class}.php";
    });

    //name of the file both local and remote...
    //the local must be in the root folder of the project
    define('__FILE_NAME__', 'access_encrypted.txt');

    //no of lines to show above and below a line where a search result was found
    //set 0 to disable
    define('__LINES_BUFFER__', 2);

    $google_drive_auth = new Codepunker\GoogleDrive\GoogleDriveAuthenticator();
    $google_drive_service = new Codepunker\GoogleDrive\GoogleDriveInteractor(new \Google_Service_Drive($google_drive_auth->getClient()));

    //download the encrypted file from google drive
    try {
        $google_drive_file = $google_drive_service->findFiles([
            'q'         => "name contains '" . __FILE_NAME__ . "' and trashed = false",
            'orderBy'   => "modifiedTime desc",
            'spaces'    => "drive"
        ], $expected = 1);
        $google_drive_file_content = $google_drive_service->downloadFile($google_drive_file->id);
    } catch (Exception $e) {
        print "An error occurred: " . $e->getMessage() . PHP_EOL;
        die;
    }

    $menu = (new CliMenuBuilder)
        ->setWidth((int)shell_exec('tput cols'))
        ->setBackgroundColour('default')
        ->setForegroundColour('default')
        ->setPadding(1)
        ->setMargin(5)
        ->setTitleSeparator('- ')
        ->addAsciiArt(file_get_contents(__DIR__ . '/asciilogo.txt'), PhpSchool\CliMenu\MenuItem\AsciiArtItem::POSITION_CENTER)
        ->addLineBreak('-')
        ->addItem('Encrypt Local File', function(CliMenu $menu) {
            CodePunker\Cli\Runner::encrypt();
        })
        ->addItem('Decrypt', function(CliMenu $menu) {
            CodePunker\Cli\Runner::decrypt();
        })
        ->addItem('Append Data', function(CliMenu $menu) {
            CodePunker\Cli\Runner::add();
        })
        ->addItem('Authenticator', function(CliMenu $menu) {
            CodePunker\Cli\Runner::totp();
        })
        ->addLineBreak('-')
        ->build();

    $menu->open();
