<?php
namespace Codepunker\Cli;

/**
 * PHP CLI enhancements
 * colors and bold styles
 * don't display password when typing in STDIN
 */
class CliHacker
{
    /**
     * receives a string and human readable styles
     * and returns a styled text to be displayed in
     * the terminal...
     * @param  string $str   [the string to be styled]
     * @param  string $style [style to be applied]
     * @return string        [styled string]
     */
    public static function style($str, $style)
    {
        $ANSI_CODES = array(
            "off"        => 0,
            "bold"       => 1,
            "red"        => 31,
            "green"      => 32,
            "yellow"     => 33,
        );
        $color_attrs = explode("+", $style);
        $ansi_str = "";
        foreach ($color_attrs as $attr) {
            $ansi_str .= "\033[" . $ANSI_CODES[$attr] . "m";
        }
        $ansi_str .= $str . "\033[" . $ANSI_CODES["off"] . "m";
        return $ansi_str;
    }

    /**
     * asks for password and get's it from stdin
     *  - nothing is displayed in CLI -
     * @return string
     */
    public static function pass($again = false)
    {
        echo ($again===false) ? "Type your password: " . PHP_EOL : "Confirm your password: " . PHP_EOL;

        $oldStyle = exec('stty -g'); //cache old style

        shell_exec('stty -echo'); //remove echo
        stream_set_blocking(STDIN, true);
            $key = rtrim(fgets(STDIN), "\n");
        exec('stty ' . $oldStyle); //put echo back

        return $key;
    }

    /**
     * Creates a Terminal instance whose input stream stays in blocking mode.
     * php-school/terminal 0.3.0 sets STDIN to non-blocking after every read,
     * which also makes STDOUT non-blocking on shared TTY file descriptors and
     * causes partial writes / corrupted ANSI output when redrawing the menu.
     */
    public static function terminal(): \PhpSchool\Terminal\Terminal
    {
        $input = new class implements \PhpSchool\Terminal\IO\InputStream {
            public function read(int $numBytes, callable $callback): void
            {
                if ($numBytes < 1) {
                    throw new \InvalidArgumentException('Number of bytes to read must be greater than 0');
                }

                stream_set_blocking(\STDIN, true);
                $callback((string) fread(\STDIN, $numBytes));
            }

            public function isInteractive(): bool
            {
                return stream_isatty(\STDIN);
            }
        };

        return new \PhpSchool\Terminal\UnixTerminal(
            $input,
            new \PhpSchool\Terminal\IO\ResourceOutputStream()
        );
    }
}
