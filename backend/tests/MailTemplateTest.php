<?php
declare(strict_types=1);

namespace Turysta\Tests;

use PHPUnit\Framework\TestCase;
use Turysta\MailTemplate;

final class MailTemplateTest extends TestCase
{
    private const URL = 'https://dwadukaty.pl/potwierdz-email?token=abc123&x=1';

    public function testVerificationContainsLinkInHtmlAndText(): void
    {
        $mail = MailTemplate::verification('Jan', self::URL, false);

        $this->assertStringContainsString(self::URL, $mail['text']);
        // W HTML adres jest zakodowany (& jako &amp;), ale musi byc w przycisku i w linku zapasowym.
        $this->assertSame(2, substr_count($mail['html'], 'href="https://dwadukaty.pl/potwierdz-email?token=abc123&amp;x=1"'));
    }

    public function testHeaderShowsDomainName(): void
    {
        $mail = MailTemplate::verification('Jan', self::URL, false);

        $this->assertStringContainsString('dwadukaty.pl</span>', $mail['html']);
        $this->assertStringContainsString('dwadukaty.pl', $mail['text']);
    }

    public function testNameIsEscapedInHtml(): void
    {
        $mail = MailTemplate::verification('<script>alert(1)</script>', self::URL, false);

        $this->assertStringNotContainsString('<script>', $mail['html']);
        $this->assertStringContainsString('&lt;script&gt;', $mail['html']);
    }

    public function testNewAddressVariantHasDifferentHeading(): void
    {
        $new = MailTemplate::verification('Jan', self::URL, true);
        $first = MailTemplate::verification('Jan', self::URL, false);

        $this->assertStringContainsString('nowy adres', $new['text']);
        $this->assertStringNotContainsString('nowy adres', $first['text']);
    }
}
