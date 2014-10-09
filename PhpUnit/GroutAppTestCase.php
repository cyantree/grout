<?php
namespace Cyantree\Grout\PhpUnit;

use Cyantree\Grout\App\App;
use PHPUnit_Framework_TestCase;

class GroutAppTestCase extends PHPUnit_Framework_TestCase
{
    /** @var App */
    public $app;

    public function __construct()
    {
        global $app;

        $this->app = $app;
    }
}
