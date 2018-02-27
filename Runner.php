<?php

    namespace CodePunker\Cli;

    use Codepunker\Cipher\OpenSsl as OpenSsl;

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
         * @param  [string] $file_content encrypted file content
         * @return void
         */
        static function decrypt($file_content)
        {
            $key = (is_null(self::$pass)) ? CliHacker::pass() : self::$pass;
            try {
                $new_file_content = OpenSsl::decrypt($key, $file_content);
                self::$pass = $key;
            } catch (\Exception $e) {
                self::$pass = null;
                echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . $e->getMessage() . "... Enter to search again", "red");
                return;
            }

            print "Type what you want to search for (Regex) or hit ENTER to download the entire file: \n";
            $tosearch = trim(fgets(STDIN));

            if (empty($tosearch)) {
                file_put_contents(__DIR__ . '/' . __FILE_NAME__, $new_file_content);
                echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . "File downloaded...", "green+bold");
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

        static function encrypt($file_content, $service, $file)
        {
            $key = CliHacker::pass();
            $key2 = CliHacker::pass(true);

            if ($key !== $key2) {
                die("Passwords didn't match. File remains unchanged" . PHP_EOL);
            }

            $file_content_local = file_get_contents(__DIR__ . '/' . __FILE_NAME__);
            $new_file_content = OpenSsl::encrypt($file_content_local, $key);
            print "File encrypted. Want to push it to drive ? (yes/no) \n";
            $push = trim(fgets(STDIN));
            do {
                if ($push == 'yes') {
                    $service->updateFile($file, $new_file_content);
                    echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . "File updated on google drive ... ", "green+bold");
                } elseif ($push == 'no') {
                    file_put_contents(__DIR__ . '/' . __FILE_NAME__, $new_file_content);
                    echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . "File updated on local storage ... ", "green+bold");
                } else {
                    echo CliHacker::style("What ?" . PHP_EOL, "red");
                }
            } while (!in_array($push, ['yes', 'no']));
        }

        static function add($file_content, $service, $file)
        {
            $key = CliHacker::pass();
            try {
                $new_file_content = OpenSsl::decrypt($key, $file_content);
            } catch (\Exception $e) {
                echo CliHacker::style(PHP_EOL . "====" . PHP_EOL . $e->getMessage() . "... Hit Enter to try again", "red");
                return;
            }

            print "Type what you want added to the file: \n";
            $toadd = trim(fgets(STDIN));
            $new_file_content .= "\n\n================\n\n\n{$toadd}\n";

            $new_file_content = OpenSsl::encrypt($new_file_content, $key);
            $service->updateFile($file, $new_file_content);

            echo CliHacker::style(PHP_EOL . "Done! File Updated " . PHP_EOL, "green+bold");
        }
    }
