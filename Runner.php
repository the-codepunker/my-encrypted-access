<?php

    namespace CodePunker\Cli;

    use Codepunker\Cipher\OpenSsl as OpenSsl;
    use Codepunker\Cli\CliHacker;
    use OTPHP\TOTP;

    /**
     * executes actions based on menu selection
     */
    class Runner
    {
        static $pass = null;

        /**
         * * decrypts the remote with the provided password
         * * if pass is correct remembers the password
         * * allows to search via regex or download the decrypted file
         * @return void
         */
        static function decrypt()
        {
            global $menu;
            global $google_drive_file_content;
            $key = (is_null(self::$pass)) ? CliHacker::pass() : self::$pass;
            try {
                $new_file_content = OpenSsl::decrypt($key, $google_drive_file_content);
                self::$pass = $key;
            } catch (\Exception $e) {
                self::$pass = null;
                echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . $e->getMessage() . "... Enter to search again", "red");
                return;
            }

            $result = $menu->askText()
                ->setPromptText('Type what you want to search for or "download" to download the entire file')
                ->setPlaceholderText('/regex|download/i')
                ->ask();

            $tosearch = trim($result->fetch());

            if ($tosearch=='download') {
                file_put_contents(__DIR__ . '/' . __FILE_NAME__, $new_file_content);
                echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . "File downloaded...", "green+bold");
            } else {
                $fileasarray = explode(PHP_EOL, $new_file_content);
                foreach ($fileasarray as $i => $k) {
                    try {
                        $search_outcome = preg_match($tosearch, $k);
                    } catch (\Throwable $th) {
                        echo CliHacker::style("Wrong regular expression... Hit enter to search again", "red") . PHP_EOL;
                        return;
                    }
                    if ($search_outcome === 1) {
                        for ($a=1; $a <= __LINES_BUFFER__; $a++) {
                            if (!isset($fileasarray[$i-$a])) {
                                break;
                            }
                            if (empty(trim($fileasarray[$i-$a]))) {
                                continue;
                            }
                            echo $fileasarray[$i-$a] . PHP_EOL . "-------" . PHP_EOL;
                        }

                        echo CliHacker::style("Found {$tosearch} on line {$i}:", "yellow+bold") . PHP_EOL;
                        echo CliHacker::style($k, "green") . PHP_EOL;

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

                echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . "Search finished ... Enter to search again", "green+bold");
            }
        }

        static function encrypt()
        {
            global $menu;
            global $google_drive_service;
            global $google_drive_file;

            $key = CliHacker::pass();
            $key2 = CliHacker::pass(true);

            if ($key !== $key2) {
                die("Passwords didn't match. File remains unchanged" . PHP_EOL);
            }

            $file_content_local = file_get_contents(__DIR__ . '/' . __FILE_NAME__);
            $new_file_content = OpenSsl::encrypt($file_content_local, $key);


            $result = $menu->askText()
                ->setPromptText('File encrypted. Want to push it to drive ? (yes/no)')
                ->setPlaceholderText('')
                ->ask();

            $push = trim($result->fetch());
            do {
                if ($push == 'yes') {
                    $google_drive_service->updateFile($google_drive_file, (string)$new_file_content);
                    echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . "File updated on google drive.", "green+bold");
                } elseif ($push == 'no') {
                    file_put_contents(__DIR__ . '/' . __FILE_NAME__, $new_file_content);
                    echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . "File updated on local storage.", "green+bold");
                } else {
                    echo CliHacker::style("What ?" . PHP_EOL, "red");
                }
            } while (!in_array($push, ['yes', 'no']));
        }

        static function initialFileDownload()
        {
            global $menu;
            global $google_drive_service;
            global $google_drive_file;
            global $google_drive_file_content;

            //download the encrypted file from google drive
            try {
                $google_drive_file = $google_drive_service->findFiles([
                    'q'         => "name contains '" . __FILE_NAME__ . "' and trashed = false",
                    'orderBy'   => "modifiedTime desc",
                    'spaces'    => "drive"
                ], $expected = 1);
                $google_drive_file_content = $google_drive_service->downloadFile($google_drive_file->id);
                $menu->close();
                init();
            } catch (\Exception $e) {
                print "File not found: " . $e->getMessage() . PHP_EOL;
                print PHP_EOL;
                print "If this is the first time you opened this app, select initial file upload" . PHP_EOL;
                die;
            }
        }

        static function initialFileUpload()
        {
            global $menu;
            global $google_drive_service;
            global $google_drive_file;

            $result = $menu->askText()
                ->setPromptText('This will overwrite any existing encrypted file. Are you sure you want to do this ? (Yes/No)')
                ->setPlaceholderText('')
                ->ask();

            $answer = strtolower(trim($result->fetch()));
            if($answer=='yes') {               
                $key = CliHacker::pass();
                $key2 = CliHacker::pass(true);

                if ($key !== $key2) {
                    die("Passwords didn't match." . PHP_EOL);
                }

                $file_content_local = file_get_contents(__DIR__ . '/' . __FILE_NAME__);
                $new_file_content = OpenSsl::encrypt($file_content_local, $key);

                $google_drive_service->uploadNewFile($new_file_content);
                echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . "File uploaded on google drive. Now you can load it.", "green+bold");

            } else {
                die("Bailed out..." . PHP_EOL);
            }

        }

        static function add()
        {
            global $menu;
            global $google_drive_file_content;
            global $google_drive_service;
            global $google_drive_file;

            $key = CliHacker::pass();
            try {
                $new_file_content = OpenSsl::decrypt($key, $google_drive_file_content);
            } catch (\Exception $e) {
                echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . $e->getMessage() . "... Hit Enter to try again", "red");
                return;
            }

            $result = $menu->askText()
                ->setPromptText('Type what you want added to the file')
                ->setPlaceholderText('')
                ->ask();

            $toadd = trim($result->fetch());
            $new_file_content .= "\n\n================\n\n\n{$toadd}\n";

            $new_file_content = OpenSsl::encrypt($new_file_content, $key);
            $google_drive_service->updateFile($google_drive_file, (string)$new_file_content);

            echo CliHacker::style(PHP_EOL . "Done! File Updated " . PHP_EOL, "green+bold");
        }

        static function totp()
        {
            global $menu;
            $secrets = json_decode(file_get_contents(__DIR__ . '/otp_secrets.json'), true);
            foreach($secrets as $provider => $secret) {
                $otp = TOTP::create($secret);
        
                $timecode = (int) floor(time()/$otp->getPeriod());
                $next_otp_at = ($timecode+1)*$otp->getPeriod();
        
                $remaining_time = $next_otp_at - time();       
                $current_otp = $otp->now();
                $next_otp = $otp->at($next_otp_at);

                echo CliHacker::style("{$provider}:", "green+bold") . CliHacker::style(" {$current_otp}. Expires in {$remaining_time}s. Then:  {$next_otp}", "red" ) . PHP_EOL;
            }
        }
    }
