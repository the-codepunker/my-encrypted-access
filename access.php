<?php

	(php_sapi_name() === 'cli') or die("cli only");
	require_once __DIR__ . '/vendor/autoload.php';
	spl_autoload_register(function($class) { require_once __DIR__ . "/{$class}.php"; });

	//name of the file both local and remote... 
	//the local must be in the root folder of the project
	define('__FILE_NAME__', 'access_encrypted.txt');

	//no of lines to show above and below a line where a search result was found
	//set 0 to disable
	define('__LINES_BUFFER__', 2);

	$auth = new GoogleDriveAuthenticator();
	$service = new GoogleDriveInteractor( new Google_Service_Drive($auth->getClient()) );

	if(empty($argv[1])) {
		print "What to do ?
		- To encrypt a local file type 'encrypt'
		- To decrypt and search in the file type 'decrypt'
		- To add something to the file and the re-upload it as encrypted type 'add'\n";
		$line = trim(fgets(STDIN));
	} else 
		$line = trim($argv[1]);

	try {
		$file = $service->findFiles([
	    	'q'			=> "name contains '" . __FILE_NAME__ . "' and trashed = false",
	    	'orderBy'	=> "modifiedTime desc",
	    	'spaces'	=> "drive"
	    ], $expected=1);
	    $file_content = $service->downloadFile($file->id);
	} catch (Exception $e) {
		print "An error occurred: " . $e->getMessage() . PHP_EOL; exit;
	}

	if($line=="encrypt") {
		$key = CliHacker::pass();
		$key2 = CliHacker::pass(true);

		if($key !== $key2)
			die("Passwords didn't match. File remains unchanged" . PHP_EOL);

		$file_content_local = file_get_contents(__DIR__ . '/' . __FILE_NAME__);
		$new_file_content = OpenSsl::encrypt($file_content_local, $key);
		print "File encrypted. Want to push it to drive ? (yes/no) \n";
		$push = trim(fgets(STDIN));
		if($push == 'yes')
			$service->updateFile($file, $new_file_content);
		else if ($push == 'no')
			file_put_contents(__DIR__ . '/' . __FILE_NAME__, $new_file_content);
		else
			echo "What ?" . PHP_EOL;
	} else if($line=="decrypt") {
		$key = CliHacker::pass();

		$new_file_content = OpenSsl::decrypt($key, $file_content);
		print "Type what you want to search for (Regex) or hit ENTER to download the entire file: \n";
		$tosearch = trim(fgets(STDIN));

		if(empty($tosearch)) {
			file_put_contents(__DIR__ . '/' . __FILE_NAME__, $new_file_content);
		} else {
			$fileasarray = explode(PHP_EOL, $new_file_content);
			foreach ($fileasarray as $i=>$k) {
				if(preg_match($tosearch, $k) === 1) {

					for ($a=1; $a <= __LINES_BUFFER__; $a++) { 
						if(!isset($fileasarray[$i-$a]))
							break;
						if(empty(trim($fileasarray[$i-$a])))
							continue;
						echo $fileasarray[$i-$a] . PHP_EOL . "-------" . PHP_EOL;
					}

					echo CliHacker::style("Found {$tosearch} on line {$i}:", "yellow+bold") . PHP_EOL;
					echo CliHacker::style($k, "green") . PHP_EOL;


					for ($a=1; $a <= __LINES_BUFFER__; $a++) { 
						if(!isset($fileasarray[$i+$a]))
							break;
						if(empty(trim($fileasarray[$i+$a])))
							continue;
						echo $fileasarray[$i+$a] . PHP_EOL . "-------" . PHP_EOL;
					}

				}
			}
		}
	} else if($line=="add") {
		$key = CliHacker::pass();
		$new_file_content = OpenSsl::decrypt($key, $file_content);

		print "Type what you want added to the file: \n";
		$toadd = trim(fgets(STDIN));
		$new_file_content .= "\n\n================\n\n\n{$toadd}\n";

		$new_file_content = OpenSsl::encrypt($new_file_content, $key);
		$service->updateFile($file, $new_file_content);
	} else {
		print "Unknown command\n";
	}
