<?php namespace Arcanedev\NoCaptcha\Tests;

use Arcanedev\NoCaptcha\NoCaptchaV2;
use Arcanedev\NoCaptcha\Utilities\Request;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class     NoCaptchaV2Test
 *
 * @package  Arcanedev\NoCaptcha\Tests
 * @author   ARCANEDEV <arcanedev.maroc@gmail.com>
 */
class NoCaptchaV2Test extends TestCase
{
    /* -----------------------------------------------------------------
     |  Properties
     | -----------------------------------------------------------------
     */

    /** @var  \Arcanedev\NoCaptcha\NoCaptchaV2 */
    private $noCaptcha;

    /* -----------------------------------------------------------------
     |  Main Methods
     | -----------------------------------------------------------------
     */

    public function setUp(): void
    {
        parent::setUp();

        $this->noCaptcha = $this->createCaptcha();
    }

    public function tearDown(): void
    {
        unset($this->noCaptcha);

        parent::tearDown();
    }

    /* -----------------------------------------------------------------
     |  Tests
     | -----------------------------------------------------------------
     */

    /** @test */
    public function it_can_get_client_url()
    {
        static::assertSame(
            'https://www.google.com/recaptcha/api.js',
            NoCaptchaV2::getClientUrl()
        );

        NoCaptchaV2::$useGlobalDomain = true;

        static::assertSame(
            'https://www.recaptcha.net/recaptcha/api.js',
            NoCaptchaV2::getClientUrl()
        );

        NoCaptchaV2::$useGlobalDomain = false;

        static::assertSame(
            'https://www.google.com/recaptcha/api.js',
            NoCaptchaV2::getClientUrl()
        );
    }

    /** @test */
    public function it_can_get_verification_url()
    {
        static::assertSame(
            'https://www.google.com/recaptcha/api/siteverify',
            NoCaptchaV2::getVerificationUrl()
        );

        NoCaptchaV2::$useGlobalDomain = true;

        static::assertSame(
            'https://www.recaptcha.net/recaptcha/api/siteverify',
            NoCaptchaV2::getVerificationUrl()
        );

        NoCaptchaV2::$useGlobalDomain = false;

        static::assertSame(
            'https://www.google.com/recaptcha/api/siteverify',
            NoCaptchaV2::getVerificationUrl()
        );
    }

    /** @test */
    public function it_can_be_instantiated()
    {
        static::assertInstanceOf(NoCaptchaV2::class, $this->noCaptcha);

        static::assertSame(
            '<script src="'.NoCaptchaV2::getClientUrl().'" async defer></script>',
            $this->noCaptcha->script()->toHtml()
        );
    }

    /** @test */
    public function it_can_be_instantiated_with_nullable_attributes()
    {
        $this->noCaptcha = $this->createCaptcha(null);
        $attributes = [
            'data-theme' => null,
            'data-type'  => null,
            'data-size'  => null
        ];

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key"></div>',
            $this->noCaptcha->display('captcha', $attributes)->toHtml()
        );
    }

    /**
     * @test
     *
     * @dataProvider provideNoCaptchaAttributes
     *
     * @param  array   $attributes
     * @param  string  $expected
     */
    public function it_can_display_with_custom_attributes(array $attributes, $expected)
    {
        static::assertSame(
            $expected, $this->createCaptcha()->display('captcha', $attributes)->toHtml()
        );
    }

    /**
     * @return array
     */
    public function provideNoCaptchaAttributes()
    {
        return [
            [
                ['data-theme' => 'light'],
                '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="light"></div>',
            ],
            [
                ['data-theme' => 'dark'],
                '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="dark"></div>',
            ],
            [
                ['data-theme' => 'transparent'], // Invalid
                '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="light"></div>',
            ],
            [
                ['data-type' => 'image'],
                '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-type="image"></div>',
            ],
            [
                ['data-type' => 'audio'],
                '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-type="audio"></div>',
            ],
            [
                ['data-type' => 'video'], // Invalid
                '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-type="image"></div>',
            ],
            [
                ['data-size' => 'normal'],
                '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-size="normal"></div>',
            ],
            [
                ['data-size' => 'compact'],
                '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-size="compact"></div>',
            ],
            [
                ['data-size' => 'huge'], // Invalid
                '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-size="normal"></div>',
            ],
        ];
    }

    /** @test */
    public function it_must_throw_invalid_type_exception_on_secret_key()
    {
        $this->expectException(\Arcanedev\NoCaptcha\Exceptions\ApiException::class);
        $this->expectExceptionMessage('The secret key must be a string value, NULL given');

        new NoCaptchaV2(null, null);
    }

    /** @test */
    public function it_must_throw_api_exception_on_empty_secret_key()
    {
        $this->expectException(\Arcanedev\NoCaptcha\Exceptions\ApiException::class);
        $this->expectExceptionMessage('The secret key must not be empty');

        new NoCaptchaV2('   ', null);
    }

    /** @test */
    public function it_must_throw_invalid_type_exception_on_site_key()
    {
        $this->expectException(\Arcanedev\NoCaptcha\Exceptions\ApiException::class);
        $this->expectExceptionMessage('The site key must be a string value, NULL given');

        new NoCaptchaV2('secret', null);
    }

    /** @test */
    public function it_must_throw_api_exception_on_empty_site_key()
    {
        $this->expectException(\Arcanedev\NoCaptcha\Exceptions\ApiException::class);
        $this->expectExceptionMessage('The site key must not be empty');

        new NoCaptchaV2('secret', '   ');
    }

    /** @test */
    public function it_can_switch_locale()
    {
        $locale = 'fr';
        $this->noCaptcha->setLang($locale);

        static::assertInstanceOf(NoCaptchaV2::class, $this->noCaptcha);
        static::assertSame(
            '<script src="'.NoCaptchaV2::getClientUrl().'?hl='.$locale.'" async defer></script>',
            $this->noCaptcha->script()->toHtml()
        );
    }

    /** @test */
    public function it_can_render_script_tag()
    {
        $tag = '<script src="'.NoCaptchaV2::getClientUrl().'" async defer></script>';

        static::assertSame($tag, $this->noCaptcha->script()->toHtml());

        // Echo out only once
        static::assertEmpty($this->noCaptcha->script()->toHtml());
    }

    /** @test */
    public function it_can_render_script_tag_with_lang()
    {
        $lang = 'fr';
        $tag  = '<script src="'.NoCaptchaV2::getClientUrl().'?hl='.$lang.'" async defer></script>';
        $this->noCaptcha = $this->createCaptcha($lang);

        static::assertSame($tag, $this->noCaptcha->script()->toHtml());

        // Not even twice
        static::assertEmpty($this->noCaptcha->script()->toHtml());
    }

    /** @test */
    public function it_can_render_script_with_callback()
    {
        $captchas = ['captcha-1', 'captcha-2'];
        $script   =
            '<script>
                window.noCaptcha = {
                    captchas: [],
                    reset: function(name) {
                        var captcha = window.noCaptcha.get(name);
        
                        if (captcha)
                            window.noCaptcha.resetById(captcha.id);
                    },
                    resetById: function(id) {
                        grecaptcha.reset(id);
                    },
                    get: function(name) {
                        return window.noCaptcha.find(function (captcha) {
                            return captcha.name === name;
                        });
                    },
                    getById: function(id) {
                        return window.noCaptcha.find(function (captcha) {
                            return captcha.id === id;
                        });
                    },
                    find: function(callback) {
                        return window.noCaptcha.captchas.find(callback);
                    },
                    render: function(name, sitekey) {
                        var captcha = {
                            id: grecaptcha.render(name, {\'sitekey\' : sitekey}),
                            name: name
                        };
                        
                        window.noCaptcha.captchas.push(captcha);
                        
                        return captcha;
                    }
                }
            </script>
            <script>
                var captchaRenderCallback = function() {
                    if (document.getElementById(\'captcha-1\')) { window.noCaptcha.render(\'captcha-1\', \'site-key\'); }
                    if (document.getElementById(\'captcha-2\')) { window.noCaptcha.render(\'captcha-2\', \'site-key\'); }
                };
            </script>
            <script src="'.NoCaptchaV2::getClientUrl().'?onload=captchaRenderCallback&render=explicit" async defer></script>';

        static::assertSame(
            array_map('trim', preg_split('/\r\n|\r|\n/', $script)),
            array_map('trim', preg_split('/\r\n|\r|\n/', $this->noCaptcha->scriptWithCallback($captchas)->toHtml()))
        );

        // Not even twice
        static::assertEmpty($this->noCaptcha->script()->toHtml());
        static::assertEmpty($this->noCaptcha->scriptWithCallback($captchas)->toHtml());
    }

    /** @test */
    public function it_can_display_captcha()
    {
        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key"></div>',
            $this->noCaptcha->display('captcha')->toHtml()
        );

        static::assertSame(
            '<div id="captcha_1" name="captcha" class="g-recaptcha" data-sitekey="site-key"></div>',
            $this->noCaptcha->display('captcha', ['id' => 'captcha_1'])->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-type="image" data-theme="light"></div>',
            $this->noCaptcha->display('captcha', [
                'data-type'  => 'image',
                'data-theme' => 'light',
            ])->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-type="audio" data-theme="dark"></div>',
            $this->noCaptcha->display('captcha', [
                'data-type'  => 'audio',
                'data-theme' => 'dark',
            ])->toHtml()
        );
    }

    /** @test */
    public function it_can_display_image_captcha()
    {
        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-type="image"></div>',
            $this->noCaptcha->image('captcha')->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="dark" data-type="image"></div>',
            $this->noCaptcha->image('captcha', ['data-theme' => 'dark'])->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="light" data-type="image"></div>',
            $this->noCaptcha->image('captcha', ['data-theme' => 'light'])->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="light" data-type="image"></div>',
            $this->noCaptcha->image('captcha', [
                'data-theme' => 'light',
                'data-type'  => 'audio', // Intruder
            ])->toHtml()
        );
    }

    /** @test */
    public function it_can_display_audio_captcha()
    {
        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-type="audio"></div>',
            $this->noCaptcha->audio('captcha')->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="dark" data-type="audio"></div>',
            $this->noCaptcha->audio('captcha', ['data-theme' => 'dark'])->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="light" data-type="audio"></div>',
            $this->noCaptcha->audio('captcha', ['data-theme' => 'light'])->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="light" data-type="audio"></div>',
            $this->noCaptcha->audio('captcha', [
                'data-theme' => 'light',
                'data-type'  => 'image', // Intruder
            ])->toHtml()
        );
    }

    /** @test */
    public function it_can_display_captcha_with_defaults()
    {
        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-type="image"></div>',
            $this->noCaptcha->display('captcha', ['data-type'  => 'video'])->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-type="image"></div>',
            $this->noCaptcha->display('captcha', ['data-type'  => true])->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="light"></div>',
            $this->noCaptcha->display('captcha', ['data-theme' => 'blue'])->toHtml()
        );

        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" data-theme="light"></div>',
            $this->noCaptcha->display('captcha', ['data-theme' => true])->toHtml()
        );
    }

    /** @test */
    public function it_can_display_captcha_with_style_attribute()
    {
        static::assertSame(
            '<div id="captcha" name="captcha" class="g-recaptcha" data-sitekey="site-key" style="transform: scale(0.77); transform-origin: 0 0;"></div>',
            $this->noCaptcha->display('captcha', [
                'style' => 'transform: scale(0.77); transform-origin: 0 0;'
            ])->toHtml()
        );
    }

    /** @test */
    public function it_can_display_invisible_captcha()
    {
        static::assertSame(
            '<button data-callback="onSubmit" class="g-recaptcha" data-sitekey="site-key">Send</button>',
            $this->noCaptcha->button('Send')->toHtml()
        );

        static::assertSame(
            '<button data-callback="submitForm" class="g-recaptcha" data-sitekey="site-key">Post the form</button>',
            $this->noCaptcha->button('Post the form', ['data-callback' => 'submitForm'])->toHtml()
        );

        static::assertSame(
            '<button data-callback="onSubmit" class="g-recaptcha" data-sitekey="site-key" data-size="invisible">Send</button>',
            $this->noCaptcha->button('Send', ['data-size' => 'invisible'])->toHtml()
        );

        static::assertSame(
            '<button data-callback="onSubmit" class="g-recaptcha" data-sitekey="site-key" data-badge="bottomright">Send</button>',
            $this->noCaptcha->button('Send', ['data-badge' => 'bottomright'])->toHtml()
        );

        static::assertSame(
            '<button data-callback="onSubmit" class="g-recaptcha" data-sitekey="site-key" data-badge="bottomright">Send</button>',
            $this->noCaptcha->button('Send', ['data-badge' => 'topright'])->toHtml()
        );
    }

    /** @test */
    public function it_can_verify()
    {
        /** @var \Arcanedev\NoCaptcha\Utilities\Request  $requestClient */
        $requestClient = $this->prophesize(Request::class);
        $requestClient->send(Argument::type('string'))
            ->willReturn('{"success": true}');

        $response = $this->noCaptcha
            ->setRequestClient($requestClient->reveal())
            ->verify('re-captcha-response');

        static::assertTrue($response->isSuccess());
    }

    /** @test */
    public function it_can_verify_psr7_request()
    {
        /**
         * @var  \Psr\Http\Message\ServerRequestInterface  $request
         * @var  \Arcanedev\NoCaptcha\Utilities\Request    $requestClient
         */
        $requestClient = $this->prophesize(Request::class);
        $requestClient->send(Argument::type('string'))
            ->willReturn('{"success": true}');

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getParsedBody()->willReturn(['g-recaptcha-response' => true]);
        $request->getServerParams()->willReturn(['REMOTE_ADDR' => '127.0.0.1']);

        $response = $this->noCaptcha
            ->setRequestClient($requestClient->reveal())
            ->verifyRequest($request->reveal());

        static::assertTrue($response->isSuccess());
    }

    /** @test */
    public function it_can_verify_with_fails()
    {
        $response = $this->noCaptcha->verify('');

        static::assertFalse($response->isSuccess());

        $client = tap($this->prophesize(Request::class), function ($client) {
            $client->send(Argument::type('string'))
                ->willReturn(json_encode([
                    'success'     => false,
                    'error-codes' => 'invalid-input-response'
                ]));
        })->reveal();

        $response = $this->noCaptcha
            ->setRequestClient($client)
            ->verify('re-captcha-response');

        static::assertFalse($response->isSuccess());
    }

    /** @test */
    public function it_can_render_captcha_with_optional_name()
    {
        static::assertSame(
            '<div class="g-recaptcha" data-sitekey="site-key"></div>',
            $this->noCaptcha->display()->toHtml()
        );
        static::assertSame(
            '<div class="g-recaptcha" data-sitekey="site-key" data-type="image"></div>',
            $this->noCaptcha->image()->toHtml()
        );
        static::assertSame(
            '<div class="g-recaptcha" data-sitekey="site-key" data-type="audio"></div>',
            $this->noCaptcha->audio()->toHtml()
        );
    }

    /** @test */
    public function it_must_throw_an_invalid_argument_exception_when_the_generic_captcha_name_is_same_as_captcha_response_name()
    {
        $this->expectException(\Arcanedev\NoCaptcha\Exceptions\InvalidArgumentException::class);
        $this->expectExceptionMessage('The captcha name must be different from "g-recaptcha-response".');

        $this->noCaptcha->display(NoCaptchaV2::CAPTCHA_NAME);
    }

    /** @test */
    public function it_must_throw_an_invalid_argument_exception_when_the_image_captcha_name_is_same_as_captcha_response_name()
    {
        $this->expectException(\Arcanedev\NoCaptcha\Exceptions\InvalidArgumentException::class);
        $this->expectExceptionMessage('The captcha name must be different from "g-recaptcha-response".');

        $this->noCaptcha->image(NoCaptchaV2::CAPTCHA_NAME);
    }

    /** @test */
    public function it_must_throw_an_invalid_argument_exception_when_the_audio_captcha_name_is_same_as_captcha_response_name()
    {
        $this->expectException(\Arcanedev\NoCaptcha\Exceptions\InvalidArgumentException::class);
        $this->expectExceptionMessage('The captcha name must be different from "g-recaptcha-response".');

        $this->noCaptcha->audio(NoCaptchaV2::CAPTCHA_NAME);
    }

    /* -----------------------------------------------------------------
     |  Other Methods
     | -----------------------------------------------------------------
     */

    /**
     * Create Captcha for testing
     *
     * @param  string|null  $lang
     *
     * @return \Arcanedev\NoCaptcha\NoCaptchaV2
     */
    private function createCaptcha($lang = null)
    {
        return new NoCaptchaV2('secret-key', 'site-key', $lang);
    }
}
