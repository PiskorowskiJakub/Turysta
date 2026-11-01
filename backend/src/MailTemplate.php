<?php
declare(strict_types=1);

namespace Turysta;

/** Szablony wiadomosci e-mail: jedna wersja HTML (style inline, uklad w tabelach) i jej odpowiednik tekstowy. */
final class MailTemplate
{
    public const BRAND = 'dwadukaty.pl';

    /**
     * Mail z potwierdzeniem adresu e-mail.
     *
     * @return array{html:string,text:string}
     */
    public static function verification(string $name, string $url, bool $newAddress): array
    {
        return self::action(
            $newAddress ? 'Potwierdź nowy adres e-mail' : 'Potwierdź adres e-mail',
            "Cześć $name!",
            $newAddress
                ? 'Zmieniłeś adres e-mail na swoim koncie. Kliknij przycisk, aby potwierdzić nowy adres.'
                : 'Cieszymy się, że dołączasz do gry. Kliknij przycisk, aby potwierdzić swój adres e-mail i dokończyć zakładanie konta.',
            'Potwierdź adres e-mail',
            $url,
            'Link działa przez 2 dni. Jeśli to nie Ty zakładałeś konto, po prostu zignoruj tę wiadomość.'
        );
    }

    /** @return array{html:string,text:string} */
    public static function action(string $heading, string $greeting, string $lead, string $buttonLabel, string $url, string $note): array
    {
        $text = "$heading\n\n$greeting\n\n$lead\n\n$buttonLabel:\n$url\n\n$note\n\n-- \n" . self::BRAND . "\n";

        $e = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $brand = $e(self::BRAND);
        $link = $e($url);

        $html = '<!DOCTYPE html><html lang="pl"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $e($heading) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#1f2a22;">'
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . $e($lead) . '</div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#1f2a22;"><tr><td align="center" style="padding:24px 12px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;border-radius:12px;overflow:hidden;background:#f3ecd8;font-family:Arial,Helvetica,sans-serif;color:#2b3a2f;">'
            . '<tr><td align="center" style="background:#34493b;padding:22px 24px;border-bottom:4px solid #e8742a;">'
            . '<span style="font-size:26px;font-weight:bold;letter-spacing:1px;color:#f3ecd8;">' . $brand . '</span>'
            . '</td></tr>'
            . '<tr><td style="padding:30px 28px 8px 28px;">'
            . '<h1 style="margin:0 0 16px 0;font-size:22px;line-height:1.3;color:#2b3a2f;">' . $e($heading) . '</h1>'
            . '<p style="margin:0 0 12px 0;font-size:16px;line-height:1.5;">' . $e($greeting) . '</p>'
            . '<p style="margin:0 0 24px 0;font-size:16px;line-height:1.5;">' . $e($lead) . '</p>'
            . '</td></tr>'
            . '<tr><td align="center" style="padding:0 28px 24px 28px;">'
            . '<a href="' . $link . '" style="display:inline-block;background:#e8742a;color:#ffffff;text-decoration:none;font-weight:bold;font-size:16px;padding:14px 28px;border-radius:10px;">' . $e($buttonLabel) . '</a>'
            . '</td></tr>'
            . '<tr><td style="padding:0 28px 8px 28px;font-size:13px;line-height:1.5;color:#55645a;">'
            . '<p style="margin:0 0 6px 0;">Jeśli przycisk nie działa, skopiuj ten adres do przeglądarki:</p>'
            . '<p style="margin:0 0 16px 0;word-break:break-all;"><a href="' . $link . '" style="color:#b9531a;">' . $link . '</a></p>'
            . '<p style="margin:0 0 20px 0;">' . $e($note) . '</p>'
            . '</td></tr>'
            . '<tr><td align="center" style="background:#e6dcc0;padding:14px 24px;font-size:12px;color:#55645a;">' . $brand . ' · gra o podróżowaniu</td></tr>'
            . '</table></td></tr></table></body></html>';

        return ['html' => $html, 'text' => $text];
    }
}
