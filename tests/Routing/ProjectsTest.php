<?php

namespace Core\Tests\Routing;

use Core\Routing\Projects;
use Core\Utils\Config;
use Core\Utils\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Regression coverage for two bugs found via PHPStan + manual tracing (this
 * class had zero test coverage before, despite running on every request via
 * Bootstrap::router()):
 *
 * 1. Projects::$currentProject was a typed nullable property with no default
 *    - reading it via `== null` (the "nothing matched, fall back to the
 *    default project" check) threw "must not be accessed before
 *    initialization" instead of comparing falsy, whenever no configured
 *    domain matched the request at all.
 * 2. getRegularExpression()'s `$pos == 0` check (loose comparison) treated
 *    strpos()'s "not found" return (false) the same as "found at position
 *    0", since `false == 0` is true in PHP - this silently populated the
 *    default-project fallback from whichever non-{lang} project happened to
 *    be processed first, instead of only the one actually flagged
 *    isDefault.
 *
 * 3. searchProject()/getRegularExpression() matched a sub-project's bare
 *    path-segment domain key (e.g. 'api') via an unanchored substring
 *    search against the *raw* userURL, which includes the request's own
 *    hostname (see URL::getUserURL()) - a key like 'api' matched *every*
 *    request to a host that merely starts with "api" (e.g.
 *    api-seguim.cuinadeprofit.cat), regardless of path, before the '{lang}'
 *    project ever got a chance to match. Found on a real production
 *    deployment, not hypothetical.
 */
class ProjectsTest extends TestCase
{

    private array $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    public function testResolvesTheProjectWhoseDomainMatchesTheRequest(): void
    {
        $this->seedConfig(array(
            'example.com/blog' => array('app' => 'Web', 'folders' => array(), 'languages' => array('ca')),
        ));
        $_SERVER['HTTP_HOST']   = 'example.com';
        $_SERVER['REQUEST_URI'] = '/blog/receptes';

        $projects = new Projects();

        $this->assertSame('Web', $projects->getProject()->getApp());
    }

    public function testThrowsWhenNoDomainMatchesAndNoneIsFlaggedDefault(): void
    {
        // regression for both bugs above: a non-{lang} project ('cronjob')
        // is deliberately present so the old `$pos == 0` bug had a chance to
        // wrongly "adopt" it as the fallback instead of correctly finding no
        // default at all
        $this->seedConfig(array(
            'cronjob'               => array('app' => 'Cronjob', 'folders' => array(), 'languages' => array('ca')),
            'only-project.example'  => array('app' => 'Web', 'folders' => array(), 'languages' => array('ca')),
        ));
        $_SERVER['HTTP_HOST']   = 'totally-unmatched-domain.example';
        $_SERVER['REQUEST_URI'] = '/nothing/matches';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No project matches the current configuration');

        new Projects();
    }

    /**
     * regression for bug 3 above - reproduces the exact real-world
     * configuration that broke: 'api' is checked before '{lang}' (config
     * key order), and the app's own base_domain host starts with the
     * literal text "api"
     */
    public function testDoesNotMatchABareDomainKeyAgainstTheRequestsOwnHostname(): void
    {
        $this->seedConfig(array(
            'api'    => array('app' => 'Api', 'folders' => array(), 'languages' => array('ca')),
            '{lang}' => array('app' => 'Web', 'folders' => array(), 'languages' => array('ca', 'es', 'en')),
        ), 'https://api-seguim.example.com/');
        $_SERVER['HTTP_HOST']   = 'api-seguim.example.com';
        $_SERVER['REQUEST_URI'] = '/ca';

        $projects = new Projects();

        $this->assertSame('Web', $projects->getProject()->getApp());
    }

    /**
     * same configuration, but an actual /api/... request still correctly
     * resolves to the Api project - the fix must not turn 'api' into a
     * dead key, only stop it matching inside the hostname
     */
    public function testStillMatchesABareDomainKeyAgainstARealPathSegment(): void
    {
        $this->seedConfig(array(
            'api'    => array('app' => 'Api', 'folders' => array(), 'languages' => array('ca')),
            '{lang}' => array('app' => 'Web', 'folders' => array(), 'languages' => array('ca', 'es', 'en')),
        ), 'https://api-seguim.example.com/');
        $_SERVER['HTTP_HOST']   = 'api-seguim.example.com';
        $_SERVER['REQUEST_URI'] = '/api/login';

        $projects = new Projects();

        $this->assertSame('Api', $projects->getProject()->getApp());
    }

    public function testFallsBackToTheProjectExplicitlyFlaggedDefault(): void
    {
        $this->seedConfig(array(
            'cronjob'  => array('app' => 'Cronjob', 'folders' => array(), 'languages' => array('ca')),
            '{lang}/'  => array(
                'app'       => 'Web',
                'folders'   => array(),
                'languages' => array('ca', 'es'),
                'isDefault' => true,
            ),
        ));
        $_SERVER['HTTP_HOST']   = 'totally-unmatched-domain.example';
        $_SERVER['REQUEST_URI'] = '/nothing/matches';

        $projects = new Projects();

        $this->assertSame('Web', $projects->getProject()->getApp());
    }

    /**
     * Config has a private constructor (singleton via getInstance()) and
     * loads its project list from disk normally - build one directly via
     * reflection instead, entirely independent of any real config files.
     */
    private function seedConfig(array $projects, string $baseDomain = 'https://example.com/'): void
    {
        $reflection = new ReflectionClass(Config::class);
        $config     = $reflection->newInstanceWithoutConstructor();

        $reflection->getProperty('projects')->setValue($config, $projects);
        $reflection->getProperty('baseDomain')->setValue($config, $baseDomain);

        $reflection->getProperty('instance')->setValue(null, $config);
    }

}
