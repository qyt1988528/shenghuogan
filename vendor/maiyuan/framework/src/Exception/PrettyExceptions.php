<?php
namespace MDK\Exception;

use Phalcon\Di\Injectable;

/**
 * Prints exception/errors backtraces using a pretty visualization.
 */
class PrettyExceptions extends Injectable
{

    /**
     * Print the backtrace.
     *
     * @var bool
     */
    protected $_showBackTrace = true;

    /**
     * Show the application's code.
     *
     * @var bool
     */
    protected $_showFiles = true;

    /**
     * Show only the related part of the application.
     *
     * @var bool
     */
    protected $_showFileFragment = false;

    /**
     * CSS theme.
     *
     * @var string
     */
    protected $_theme = 'default';

    /**
     * Pretty Exceptions.
     *
     * @var string
     */
    protected $_uri = '/pretty-exceptions/';

    /**
     * Flag to control that only one exception/error is show at time
     */
    static protected $_showActive = false;

    /**
     * Set if the application's files must be opened an showed as part of the backtrace.
     *
     * @param boolean $showFiles Flag to show files.
     *
     * @return $this
     */
    public function showFiles($showFiles)
    {
        $this->_showFiles = $showFiles;
        return $this;
    }

    /**
     * Set if only the file fragment related to the exception must be shown instead of the complete file.
     *
     * @param boolean $showFileFragment Show flag.
     *
     * @return $this
     */
    public function showFileFragment($showFileFragment)
    {
        $this->_showFileFragment = $showFileFragment;
        return $this;
    }

    /**
     * Change the base uri for css/javascript sources.
     *
     * @param string $uri Base uri.
     *
     * @return $this
     */
    public function setBaseUri($uri)
    {
        $this->_uri = $uri;
        return $this;
    }

    /**
     * Get base uri.
     *
     * @return string
     */
    public function getBaseUri()
    {
        return $this->getDI()->get('url')->get($this->_uri);
    }

    /**
     * Change the CSS theme.
     *
     * @param string $theme Theme name.
     *
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->_theme = $theme;
        return $this;
    }

    /**
     * Set if the exception/error backtrace must be shown.
     *
     * @param boolean $showBackTrace Show flag.
     *
     * @return $this
     */
    public function showBackTrace($showBackTrace)
    {
        $this->_showBackTrace = $showBackTrace;
        return $this;
    }

    /**
     * Returns the css sources.
     *
     * @return string
     */
    public function getCssSources()
    {
        return '<style>body{font-family:Consolas,Lucida Console,Monaco,Andale Mono,"MS Gothic",monospace;background:#FBF6EF;}.str,.atv{color:#dcc175;}.kwd{color:#4D8DFF;}.com{color:#66747B;}.typ,.tag{color:#FFE000;}.lit{color:#5AB889;}.pun{color:#dadada;}.pln{color:#F1F2F3;}.atn{color:#E0E2E4;}.dec{color:purple;}pre.prettyprint{font-family:Consolas,Lucida Console,Monaco,Andale Mono,"MS Gothic",monospace;border:0px solid #888;margin-left:5px;width:99%;font-size:13px;letter-spacing:1px;font-weight:normal;}ol.linenums{margin-top:0;margin-bottom:0;}.prettyprint{background-color:#2f2f2f;color:#e6e6e6}li.L0,li.L1,li.L2,li.L3,li.L4,li.L5,li.L6,li.L7,li.L8,li.L9{color:#555;list-style-type:decimal;}li.L1,li.L3,li.L5,li.L7,li.L9{background:#2a2a2a;}li.highlight{background:#1a1a1a;}.error-main{background:#888;color:#ffffff;padding:10px;padding-top:14px;font-size:14px;}.error-scroll{height:250px;overflow-y:scroll;}.error-backtrace{text-align:center;}.error-backtrace table{background-color:#FBF6EF;margin-top:5px;width:99%;}.error-backtrace td{color:#373536;font-size:12px;padding:3px;}.error-backtrace a{color:#373536;text-decoration:none;}.error-backtrace a:hover{text-decoration:underline;}.error-backtrace td.error-number{color:#807B70;width:20px;}.error-backtrace .error-file,.error-main .error-file{font-size:11px;margin-top:3px;}.error-main .error-file{color:#fafafa;}.error-backtrace .error-class{color:#2A2A2A;}.error-backtrace .error-parameter{color:#807B70;}.error-backtrace .error-function{color:#FA1111;}.error-backtrace pre{text-align:left;}.version{font-family:sans-serif;text-align:right;color:#807B70;background:#EDE9DF;padding:10px;font-size:12px;}.version a{color:#807B70;}</style>';
    }

    /**
     * Returns the current framework version.
     *
     * @return string
     */
    public function getVersion()
    {
        if (class_exists("\Phalcon\Version")) {
            $version = \Phalcon\Version::get();
        } else {
            $version = "git-master";
        }
        $parts = explode(' ', $version);
        return '<div class="version">
			Phalcon Framework <a target="_new" href="https://docs.phalconphp.com/zh/">' . $version . '</a>
		</div>';
    }

    /**
     * Escape string.
     *
     * @param string $value The value.
     *
     * @return string
     */
    protected function _escapeString($value)
    {
        $value = str_replace("\n", "\\n", $value);
        $value = htmlentities($value, ENT_COMPAT, 'utf-8');
        return $value;
    }

    /**
     * Dump array.
     *
     * @param array $argument An array to dump.
     * @param int   $n        How deep?
     *
     * @return int|string
     */
    protected function _getArrayDump($argument, $n = 0)
    {
        if ($n < 3 && count($argument) > 0 && count($argument) < 8) {
            $dump = array();
            foreach ($argument as $k => $v) {
                if (is_scalar($v)) {
                    if ($v === '') {
                        $dump[] = $k . ' => (empty string)';
                    } else {
                        $dump[] = $k . ' => ' . $this->_escapeString($v);
                    }
                } else {

                    if (is_array($v)) {
                        $dump[] = $k . ' => Array(' . $this->_getArrayDump($v, $n + 1) . ')';
                        continue;
                    }

                    if (is_object($v)) {
                        $dump[] = $k . ' => Object(' . get_class($v) . ')';
                        continue;
                    }

                    if (is_null($v)) {
                        $dump[] = $k . ' => null';
                        continue;
                    }

                    $dump[] = $k . ' => ' . $v;
                }
            }
            return join(', ', $dump);
        }
        return count($argument);
    }

    /**
     * Shows a backtrace item.
     *
     * @param int   $n     Count.
     * @param array $trace Trace result.
     *
     * @return void
     */
    protected function _showTraceItem($n, $trace)
    {
        echo '<tr><td align="right" valign="top" class="error-number">#', $n, '</td><td>';
        if (isset($trace['class'])) {
            if (preg_match('/^Phalcon/', $trace['class'])) {
                echo '<span class="error-class"><a target="_new" href="http://docs.phalconphp.com/en/latest/api/',
                str_replace('\\', '_', $trace['class']), '.html">', $trace['class'], '</a></span>';
            } else {
                $classReflection = new \ReflectionClass($trace['class']);
                if ($classReflection->isInternal()) {
                    echo '<span class="error-class"><a target="_new" href="http://php.net/manual/en/class.',
                    str_replace('_', '-', strtolower($trace['class'])), '.php">', $trace['class'], '</a></span>';
                } else {
                    echo '<span class="error-class">', $trace['class'], '</span>';
                }
            }
            echo $trace['type'];
        }

        if (isset($trace['class'])) {
            echo '<span class="error-function">', $trace['function'], '</span>';
        } else {
            if (function_exists($trace['function'])) {
                $functionReflection = new \ReflectionFunction($trace['function']);
                if ($functionReflection->isInternal()) {
                    echo '<span class="error-function"><a target="_new" href="http://php.net/manual/en/function.',
                    str_replace('_', '-', $trace['function']), '.php">', $trace['function'], '</a></span>';
                } else {
                    echo '<span class="error-function">', $trace['function'], '</span>';
                }
            } else {
                echo '<span class="error-function">', $trace['function'], '</span>';
            }
        }

        if (isset($trace['args'])) {
            $this->_echoArgs($trace['args']);
        }

        if (isset($trace['file'])) {
            echo '<br/><span class="error-file">', $trace['file'], ' (', $trace['line'], ')</span>';
        }

        echo '</td></tr>';

        if ($this->_showFiles) {
            if (isset($trace['file'])) {
                $this->_echoFile($trace['file'], $trace['line']);

            }
        }
    }

    /**
     * Echo error arguments.
     *
     * @param array $args Arguments.
     *
     * @return void
     */
    protected function _echoArgs($args)
    {
        $arguments = array();
        foreach ($args as $argument) {
            if (is_scalar($argument)) {

                if (is_bool($argument)) {
                    if ($argument) {
                        $arguments[] = '<span class="error-parameter">true</span>';
                    } else {
                        $arguments[] = '<span class="error-parameter">null</span>';
                    }
                    continue;
                }

                if (is_string($argument)) {
                    $argument = $this->_escapeString($argument);
                }

                $arguments[] = '<span class="error-parameter">' . $argument . '</span>';
            } else {
                if (is_object($argument)) {
                    if (method_exists($argument, 'dump')) {
                        $arguments[] = '<span class="error-parameter">Object(' .
                            get_class($argument) . ': ' . $this->_getArrayDump($argument->dump()) . ')</span>';
                    } else {
                        $arguments[] = '<span class="error-parameter">Object(' . get_class($argument) . ')</span>';
                    }
                } else {
                    if (is_array($argument)) {
                        $arguments[] = '<span class="error-parameter">Array(' .
                            $this->_getArrayDump($argument) . ')</span>';
                    } else {
                        if (is_null($argument)) {
                            $arguments[] = '<span class="error-parameter">null</span>';
                            continue;
                        }
                    }
                }
            }
        }
        echo '(' . join(', ', $arguments) . ')';
    }

    /**
     * Show files data.
     *
     * @param string $file File name.
     * @param int    $line Line number.
     *
     * @return void
     */
    protected function _echoFile($file, $line)
    {
        echo '</table>';
        $lines = file($file);

        if ($this->_showFileFragment) {
            $numberLines = count($lines);
            $firstLine = ($line - 7) < 1 ? 1 : $line - 7;
            $lastLine = ($line + 5 > $numberLines ? $numberLines : $line + 5);
            echo "<pre class='prettyprint highlight:" . $firstLine . ":" . $line . " linenums:" .
                $firstLine . "'>";
        } else {
            $firstLine = 1;
            $lastLine = count($lines) - 1;
            echo "<pre class='prettyprint highlight:" . $firstLine . ":" . $line . " linenums error-scroll'>";
        }

        for ($i = $firstLine; $i <= $lastLine; ++$i) {

            if ($this->_showFileFragment) {
                if ($i == $firstLine) {
                    if (preg_match('#\*\/$#', rtrim($lines[$i - 1]))) {
                        $lines[$i - 1] = str_replace("* /", "  ", $lines[$i - 1]);
                    }
                }
            }

            if ($lines[$i - 1] != PHP_EOL) {
                $lines[$i - 1] = str_replace("\t", "  ", $lines[$i - 1]);
                echo htmlentities($lines[$i - 1], ENT_COMPAT, 'UTF-8');
            } else {
                echo '&nbsp;' . "\n";
            }
        }
        echo '</pre>';
        echo '<table cellspacing="0">';
    }

    /**
     * Handles exceptions.
     *
     * @param \Exception $e Exception object.
     *
     * @return boolean
     */
    public function handleException($e)
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (self::$_showActive) {
            echo $e->getMessage();
            return;
        }

        self::$_showActive = true;

        echo '<html><head><title>Exception - ',
        get_class($e),
        ': ',
        $e->getMessage(),
        '</title>',
        $this->getCssSources(), '</head><body>';

        echo '<div class="error-main">
			', get_class($e), ': ', $e->getMessage(), '
			<br/><span class="error-file">', $e->getFile(), ' (', $e->getLine(), ')</span>
		</div>';

        if ($this->_showBackTrace) {
            echo '<div class="error-backtrace"><table cellspacing="0">';
            foreach ($e->getTrace() as $n => $trace) {
                $this->_showTraceItem($n, $trace);
            }
            echo '</table></div>';
        }

        echo $this->getVersion() . '</body></html>';
        self::$_showActive = false;

        return true;
    }

    /**
     * Handles errors/warnings/notices.
     *
     * @param int    $errorCode    PHP error code.
     * @param string $errorMessage Related message.
     * @param string $errorFile    In what file.
     * @param int    $errorLine    In what line.
     *
     * @return bool
     */
    public function handleError($errorCode, $errorMessage, $errorFile, $errorLine)
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (self::$_showActive) {
            echo $errorMessage;
            return false;
        }

        if (!(error_reporting() & $errorCode)) {
            return false;
        }

        self::$_showActive = true;

        header("Content-type: text/html");

        echo '<html><head><title>Exception - ', $errorMessage, '</title>', $this->getCssSources(), '</head><body>';

        echo '<div class="error-main">
			', $errorMessage, '
			<br/><span class="error-file">', $errorFile, ' (', $errorLine, ')</span>
		</div>';

        if ($this->_showBackTrace) {
            echo '<div class="error-backtrace"><table cellspacing="0">';
            foreach (debug_backtrace() as $n => $trace) {
                if ($n == 0) {
                    continue;
                }
                $this->_showTraceItem($n, $trace);
            }
            echo '</table></div>';
        }

        echo $this->getVersion() . '</body></html>';
        self::$_showActive = false;

        return true;
    }
}