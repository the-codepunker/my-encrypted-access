<?php
    use PhpSchool\CliMenu\CliMenu;
    use PhpSchool\CliMenu\CliMenuBuilder;

    (php_sapi_name() === 'cli') or die("cli only");
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

    $auth = new Codepunker\GoogleDrive\GoogleDriveAuthenticator();
    $service = new Codepunker\GoogleDrive\GoogleDriveInteractor(new Google_Service_Drive($auth->getClient()));

    try {
        $file = $service->findFiles([
            'q'         => "name contains '" . __FILE_NAME__ . "' and trashed = false",
            'orderBy'   => "modifiedTime desc",
            'spaces'    => "drive"
        ], $expected = 1);
        $file_content = $service->downloadFile($file->id);
    } catch (Exception $e) {
        print "An error occurred: " . $e->getMessage() . PHP_EOL;
        exit;
    }

    $cb = function (CliMenu $menu) use ($file_content, $service, $file) {
        $item =  $menu->getSelectedItem()->getText();
        switch ($item) {
            case 'Encrypt Local File':
                CodePunker\Cli\Runner::encrypt($file_content, $service, $file);
                break;
            case 'Append Data':
                CodePunker\Cli\Runner::add($file_content, $service, $file);
                break;
            case 'Decrypt':
            default:
                CodePunker\Cli\Runner::decrypt($file_content);
                break;
        }
    };

    $art = <<<ART
    ##### ;#####  ,####@  ,####:  
   #####' ######` ,##@### ,####:  
  `##,   ,##` `##.,#@ `##.,##`  
  .##    :##   ##:,#@  ##:,####`  
   `##; `  ##, .##.,#@  ##.,##`    
   ###### #####+. ,###### ,####@  
    #####  @####  ,####@  ,####@  
ART;

    $menu = (new CliMenuBuilder)
        ->addAsciiArt($art, PhpSchool\CliMenu\MenuItem\AsciiArtItem::POSITION_CENTER)
        ->addLineBreak('-')
        ->addItem('Encrypt Local File', $cb)
        ->addItem('Decrypt', $cb)
        ->addItem('Append Data', $cb)
        ->addLineBreak('-')
        ->build();

    $menu->open();
