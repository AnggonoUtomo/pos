<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\ParameterizedHeader;

class MimeHeaderSecurityTest extends TestCase
{
    #[DataProvider('invalidParameterNames')]
    public function test_invalid_names_are_rejected_at_each_parameter_entry_point(string $name, string $entry): void
    {
        $this->expectException(\InvalidArgumentException::class);
        if ($entry === 'constructor') {
            new ParameterizedHeader('Content-Disposition', 'attachment', [$name => 'document.pdf']);
        } elseif ($entry === 'single') {
            (new ParameterizedHeader('Content-Disposition', 'attachment'))->setParameter($name, 'document.pdf');
        } elseif ($entry === 'bulk') {
            (new ParameterizedHeader('Content-Disposition', 'attachment'))->setParameters([$name => 'document.pdf']);
        } else {
            (new Headers)->addParameterizedHeader('Content-Disposition', 'attachment', [$name => 'document.pdf']);
        }
    }

    public static function invalidParameterNames(): iterable
    {
        foreach (["filename\r\nX-Injected: yes", "filename\nX-Injected: yes", 'file name', "file\tname", 'file;name', 'file=name', '', "filename\x00"] as $name) {
            foreach (['constructor', 'single', 'bulk', 'headers'] as $entry) {
                yield [$name, $entry];
            }
        }
    }

    public function test_normal_parameters_quoted_values_and_email_attachments_still_work(): void
    {
        $header = new ParameterizedHeader('Content-Disposition', 'attachment', ['filename' => 'bukti transaksi.pdf']);
        $this->assertStringContainsString('filename="bukti transaksi.pdf"', $header->toString());
        $header->setParameter('x-shop_reference', 'INV-001');
        $this->assertSame('INV-001', $header->getParameter('x-shop_reference'));
        $email = (new Email)->from('shop@example.test')->to('operator@example.test')
            ->subject('Bukti transaksi')->text('Lampiran transaksi latihan.')
            ->attach('fixture-only', 'bukti transaksi.pdf', 'application/pdf');
        $this->assertStringContainsString('bukti transaksi.pdf', $email->toString());
        $this->assertStringContainsString('application/pdf', $email->toString());
    }
}
