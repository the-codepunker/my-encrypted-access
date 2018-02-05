<?php

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

    if (empty($argv[1])) {
        print "What to do ?
        - To encrypt a local file type 'encrypt'
        - To decrypt and search in the file type 'decrypt'
        - To keep the decrypted file in memory and search in it type 'process'
        - To add something to the file and the re-upload it as encrypted type 'add'\n";
        $line = trim(fgets(STDIN));
    } else {
        $line = trim($argv[1]);
    }

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

    if ($line=="encrypt") {
        $key = Codepunker\Cli\CliHacker::pass();
        $key2 = Codepunker\Cli\CliHacker::pass(true);

        if ($key !== $key2) {
            die("Passwords didn't match. File remains unchanged" . PHP_EOL);
        }

        $file_content_local = file_get_contents(__DIR__ . '/' . __FILE_NAME__);
        $new_file_content = OpenSsl::encrypt($file_content_local, $key);
        print "File encrypted. Want to push it to drive ? (yes/no) \n";
        $push = trim(fgets(STDIN));
        if ($push == 'yes') {
            $service->updateFile($file, $new_file_content);
        } elseif ($push == 'no') {
            file_put_contents(__DIR__ . '/' . __FILE_NAME__, $new_file_content);
        } else {
            echo "What ?" . PHP_EOL;
        }
    } elseif ($line=="process") {
        $key = Codepunker\Cli\CliHacker::pass();
        $new_file_content = Codepunker\Cipher\OpenSsl::decrypt($key, $file_content);

        while(1) {            
            print "Type what you want to search for (Regex): \n";
            $tosearch = trim(fgets(STDIN));

            if(empty($tosearch))
                continue;

            $fileasarray = explode(PHP_EOL, $new_file_content);
            foreach ($fileasarray as $i => $k) {
                if (preg_match($tosearch, $k) === 1) {
                    for ($a=1; $a <= __LINES_BUFFER__; $a++) {
                        if (!isset($fileasarray[$i-$a])) {
                            break;
                        }
                        if (empty(trim($fileasarray[$i-$a]))) {
                            continue;
                        }
                        echo $fileasarray[$i-$a] . PHP_EOL . "-------" . PHP_EOL;
                    }

                    echo Codepunker\Cli\CliHacker::style("Found {$tosearch} on line {$i}:", "yellow+bold") . PHP_EOL;
                    echo Codepunker\Cli\CliHacker::style($k, "green") . PHP_EOL;

                    for ($a=1; $a <= __LINES_BUFFER__; $a++) {
                        if (!isset($fileasarray[$i+$a])) {
                            break;
                        }
                        if (empty(trim($fileasarray[$i+$a]))) {
                            continue;
                        }
                        echo $fileasarray[$i+$a] . PHP_EOL . "-------" . PHP_EOL;
                    }
                }
            }
        }
    } elseif ($line=="decrypt") {
        $key = Codepunker\Cli\CliHacker::pass();

        $new_file_content = Codepunker\Cipher\OpenSsl::decrypt($key, $file_content);
        print "Type what you want to search for (Regex) or hit ENTER to download the entire file: \n";
        $tosearch = trim(fgets(STDIN));

        if (empty($tosearch)) {
            file_put_contents(__DIR__ . '/' . __FILE_NAME__, $new_file_content);
        } else {
            $fileasarray = explode(PHP_EOL, $new_file_content);
            foreach ($fileasarray as $i => $k) {
                if (preg_match($tosearch, $k) === 1) {
                    for ($a=1; $a <= __LINES_BUFFER__; $a++) {
                        if (!isset($fileasarray[$i-$a])) {
                            break;
                        }
                        if (empty(trim($fileasarray[$i-$a]))) {
                            continue;
                        }
                        echo $fileasarray[$i-$a] . PHP_EOL . "-------" . PHP_EOL;
                    }

                    echo Codepunker\Cli\CliHacker::style("Found {$tosearch} on line {$i}:", "yellow+bold") . PHP_EOL;
                    echo Codepunker\Cli\CliHacker::style($k, "green") . PHP_EOL;


                    for ($a=1; $a <= __LINES_BUFFER__; $a++) {
                        if (!isset($fileasarray[$i+$a])) {
                            break;
                        }
                        if (empty(trim($fileasarray[$i+$a]))) {
                            continue;
                        }
                        echo $fileasarray[$i+$a] . PHP_EOL . "-------" . PHP_EOL;
                    }
                }
            }
        }
    } elseif ($line=="add") {
        $key = Codepunker\Cli\CliHacker::pass();
        $new_file_content = Codepunker\Cipher\OpenSsl::decrypt($key, $file_content);

        print "Type what you want added to the file: \n";
        $toadd = trim(fgets(STDIN));
        $new_file_content .= "\n\n================\n\n\n{$toadd}\n";

        $new_file_content = Codepunker\Cipher\OpenSsl::encrypt($new_file_content, $key);
        $service->updateFile($file, $new_file_content);
    } else {
        print "Unknown command\n";
    }
