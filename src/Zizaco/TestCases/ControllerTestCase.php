<?php namespace Zizaco\TestCases;

use Session;
use Symfony\Component\HttpKernel\Exception\HttpException;
use URL;

class ControllerTestCase extends TestCase
{

    /**
     * Will contain the parameters of the next request
     *
     * @var array
     */
    protected $requestInput = [];

    /**
     * Will the last HttpException caught
     *
     * @var HttpException
     */
    protected $lastException;

    /**
     * The Symfony's DomCrawler of the last request
     *
     * @var \Symfony\Component\DomCrawler\Crawler
     */
    protected $crawler;

    /**
     * Request an URL by the action name
     *
     * @param string $method
     * @param string $action
     * @param array  $params
     *
     * @return ControllerTestCase this for method chaining.
     */
    public function requestAction($method, $action, $params = [])
    {
        $actionUrl = URL::action($action, $params);

        if (! $actionUrl) {
            trigger_error("Action '$action' does not exist");
        }

        return $this->requestUrl($method, $actionUrl, $params);
    }

    /**
     * Request an URL
     *
     * @param string $method
     * @param string $url
     * @param array  $params
     *
     * @return ControllerTestCase this for method chaining.
     */
    public function requestUrl($method, $url, $params = [])
    {
        try {
            // The following method returns Symfony's DomCrawler
            $this->crawler = $this->call(
                $method,
                $url,
                array_merge($params, $this->requestInput)
            );
        } catch (HttpException $e) {
            // Store the HttpException in order to check it later
            $this->lastException = $e;
        }

        return $this; // for method chaining
    }

    /**
     * Set the post parameters and return this for chainable
     * method call
     *
     * @param array $params Post parameters array.
     *
     * @return mixed this.
     */
    public function withInput($params)
    {
        $this->requestInput = $params;

        return $this;
    }

    /**
     * Asserts if the status code is correct
     *
     * @param int $code Correct status code
     *
     * @return void
     */
    public function assertStatusCode($code)
    {
        if ($this->lastException) {
            $realCode = $this->lastException->getStatusCode();
        } else {
            $realCode = $this->response->getStatusCode();
        }

        $this->assertEquals($code, $realCode, "Response was not $code, status code was $realCode");
    }

    /**
     * Asserts if the request was Ok (200)
     *
     * @return void
     */
    public function assertRequestOk()
    {
        $this->assertStatusCode(200);
    }

    /**
     * Asserts if page was redirected correctly
     *
     * @param string $location Location where it should be redirected
     *
     * @return void
     */
    public function assertRedirection($location = null)
    {
        if ($this->lastException) {
            $statusCode = $this->lastException->getStatusCode();
        } elseif ($this->response) {
            $statusCode = $this->response->getStatusCode();
        } else {
            $statusCode = null;
        }

        $isRedirection = in_array($statusCode, [201, 301, 302, 303, 307, 308]);

        $this->assertTrue($isRedirection, "Last request was not a redirection. Status code was " . $statusCode);

        if ($location) {
            if (! strpos($location, '://')) {
                $location = 'http://:' . $location;
            }

            $this->assertEquals(
                $location, $this->response->headers->get('Location'), 'Page was not redirected to the correct place'
            );
        }

    }

    /**
     * Asserts if the session variable is correct
     *
     * @param string $name  Session variable name.
     * @param mixed  $value Session variable value.
     *
     * @return void.
     */
    public function assertSessionHas($name, $value = null)
    {
        $this->assertTrue(Session::has($name), "Session doesn't contain '$name'");

        if ($value) {
            $this->assertContains($value, Session::get($name), "Session '$name' are not equal to $value");
        }
    }

    public function getBodyHtml()
    {
        return $this->crawler->html();
    }

    public function assertBodyHasHtml($needle)
    {
        $html = $this->getBodyHtml();

        $needle = (array) $needle;

        foreach ($needle as $singleNeedle) {
            $this->assertContains($singleNeedle, $html, "Body text does not contain '$singleNeedle'");
        }
    }

    public function getBodyText()
    {
        $text = $this->getBodyHtml();
        $text = strip_tags($text); // Strip tags
        $text = str_replace("\n", " ", $text); // Replaces newline with space
        $text = preg_replace('/\s\s+/', ' ', $text);// Trim spaces between words

        return $text;
    }

    public function assertBodyHasText($needle)
    {
        $text = $this->getBodyText();

        $needle = (array) $needle;

        foreach ($needle as $singleNeedle) {
            $this->assertContains($singleNeedle, $text, "Body text does not contain '$singleNeedle'");
        }
    }

    public function assertBodyHasNotText($needle)
    {
        $text = $this->getBodyText();

        $needle = (array) $needle;

        foreach ($needle as $singleNeedle) {
            $this->assertNotContains($singleNeedle, $text, "Body text does not contain '$singleNeedle'");
        }
    }
}
