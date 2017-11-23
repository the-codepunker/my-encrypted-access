<?php

	(php_sapi_name() === 'cli') or die("cli only");
	require_once __DIR__ . '/vendor/autoload.php';
	define('__FILE_NAME__', 'access_encrypted.txt');

	spl_autoload_register(function($class) { require_once __DIR__ . "/{$class}.php"; });

	$auth = new GoogleDriveAuthenticator();
	$service = new GoogleDriveInteractor( new Google_Service_Drive($auth->getClient()) );

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

	print "What to do ?
	- To encrypt a remote file and push it back up to gDrive type 'encrypt remote'
	- To encrypt a local file type 'encrypt local'
	- To decrypt and search in the file type 'decrypt'
	- To add something to the file and the re-upload it as encrypted type 'add'\n";
	$line = trim(fgets(STDIN));

	if($line=="encrypt remote") {
		print "Type your password: \n";
		$key = trim(fgets(STDIN));
		$new_file_content = OpenSsl::encrypt($file_content, $key);
		$service->updateFile($file, $new_file_content);
	} else if($line=="encrypt local") {
		print "Type your password: \n";
		$key = trim(fgets(STDIN));
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
		print "Type your password: \n";
		$key = trim(fgets(STDIN));
		$new_file_content = OpenSsl::decrypt($key, $file_content);

		print "Type what you want to search for (Regex) or hit ENTER to download the entire file: \n";
		$tosearch = trim(fgets(STDIN));

		if(empty($tosearch)) {
			file_put_contents(__DIR__ . '/' . __FILE_NAME__, $new_file_content);
		} else {
			$fileasarray = explode(PHP_EOL, $new_file_content);
			foreach ($fileasarray as $i=>$k) {
				if(preg_match($tosearch, $k) === 1) {
					echo "Found {$tosearch} on line {$i}: \n";
					echo $k . PHP_EOL;
				}
			}
		}

	} else if($line=="add") {
		print "Type your password: \n";
		$key = trim(fgets(STDIN));
		$new_file_content = OpenSsl::decrypt($key, $file_content);

		print "Type what you want added to the file: \n";
		$toadd = trim(fgets(STDIN));
		$new_file_content .= "\n\n================\n\n\n{$toadd}\n";

		$new_file_content = OpenSsl::encrypt($new_file_content, $key);
		$service->updateFile($file, $new_file_content);
	} else {
		print "Unknown command\n";
	}
