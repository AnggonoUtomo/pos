<?php

declare(strict_types=1);

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$root = str_replace('\\', '/', dirname(__DIR__, 2));
$temporary = $root.'/storage/framework/testing/user-guide';
$output = $root.'/output/pdf/user-guide';
$environment = new Environment(['html_input' => 'strip', 'allow_unsafe_links' => false]);
$environment->addExtension(new CommonMarkCoreExtension);
$environment->addExtension(new TableExtension);
$converter = new MarkdownConverter($environment);
$escape = fn (string $text): string => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$roles = ['administrator-sistem', 'pemilik-supervisor', 'admin-master', 'kasir', 'staf-gudang', 'staf-pembelian', 'staf-keuangan'];
$publications = ['panduan-global-pos' => ['01-alur-global.md', '02-panduan-modul.md', '03-role-dan-akses.md']];
foreach ($roles as $role) {
    $publications[$role] = ['roles/'.$role.'.md'];
}

$knownPermissions = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app/Modules', FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php' || ! str_contains(str_replace('\\', '/', $file->getPathname()), '/Presentation/Controllers/')) {
        continue;
    }
    foreach (token_get_all(file_get_contents($file->getPathname())) as $token) {
        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $value = substr($token[1], 1, -1);
            if (str_starts_with($value, 'can:')) {
                $value = substr($value, 4);
            }
            if (preg_match('/^(platform|inventory|sales|purchasing|finance|reporting)\.[a-z.-]+$/', $value)) {
                $knownPermissions[$value] = true;
            }
        }
    }
}

function markdownDocument(string $html): DOMDocument
{
    $document = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body>'.$html.'</body></html>');
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    return $document;
}

$documents = [];
$hashes = [];
$links = 0;
$permissions = [];
$pending = [];
$sources = array_merge(['README.md'], ...array_values($publications));
foreach ($sources as $source) {
    $path = $root.'/docs/user-guide/'.$source;
    $markdown = file_get_contents($path);
    $hashes['docs/user-guide/'.$source] = hash('sha256', $markdown);
    $document = markdownDocument((string) $converter->convert($markdown));
    foreach ($document->getElementsByTagName('a') as $anchor) {
        $href = $anchor->getAttribute('href');
        if ($href === '' || str_starts_with($href, '#') || parse_url($href, PHP_URL_SCHEME)) {
            continue;
        }
        $target = urldecode(explode('#', $href, 2)[0]);
        $resolved = realpath(dirname($path).'/'.$target);
        if ($resolved === false) {
            $planned = '../../output/pdf/user-guide/';
            if ($source === 'README.md' && str_starts_with($target, $planned)
                && isset($publications[basename($target, '.pdf')])) {
                $pending[] = $target;

                continue;
            }
            throw new RuntimeException('Tautan tidak ditemukan: '.$source.' -> '.$href);
        }
        if (! str_starts_with(str_replace('\\', '/', $resolved), $root.'/')) {
            throw new RuntimeException('Tautan keluar workspace: '.$href);
        }
        $links++;
    }
    foreach ($document->getElementsByTagName('code') as $code) {
        $value = trim($code->textContent);
        if (preg_match('/^(platform|inventory|sales|purchasing|finance|reporting)\.[a-z.-]+$/', $value)) {
            if (! isset($knownPermissions[$value])) {
                throw new RuntimeException('Permission bukan authorization controller aktif: '.$value);
            }
            $permissions[$value] = true;
        }
    }
    $documents[$source] = $document;
}

if (in_array('--check', $argv, true)) {
    echo json_encode(['markdown' => count($documents), 'localLinks' => $links, 'permissionKeys' => count($permissions), 'pendingPdfs' => count($pending)], JSON_PRETTY_PRINT).PHP_EOL;
    exit;
}

foreach ([$temporary, $output] as $directory) {
    if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
        throw new RuntimeException('Gagal membuat folder: '.$directory);
    }
}

$css = file_get_contents(__DIR__.'/user-guide.css');
$manifest = ['edition' => '27 Oktober 2026', 'sourceRevision' => 'af410c1', 'sourceSha256' => $hashes, 'publications' => []];
foreach ($publications as $name => $files) {
    $global = $name === 'panduan-global-pos';
    $title = $global ? 'Panduan Pengguna POS' : $documents[$files[0]]->getElementsByTagName('h1')->item(0)->textContent;
    $chapters = [];
    $contents = [];
    $chapterIds = [];
    foreach ($files as $position => $file) {
        $chapterIds[$file] = 'bab-'.($position + 1);
    }
    foreach ($files as $position => $file) {
        $document = clone $documents[$file];
        $xpath = new DOMXPath($document);
        $index = 0;
        foreach ($xpath->query('//h1|//h2|//h3') as $heading) {
            $id = $index === 0 ? $chapterIds[$file] : $chapterIds[$file].'-'.$index;
            $heading->setAttribute('id', $id);
            if ($heading->tagName === 'h1' || $heading->tagName === 'h2') {
                $contents[] = '<li class="toc-'.$heading->tagName.'"><a href="#'.$id.'">'.$escape($heading->textContent).'</a></li>';
            }
            $index++;
        }
        foreach ($document->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');
            if ($href === '' || str_starts_with($href, '#') || parse_url($href, PHP_URL_SCHEME)) {
                continue;
            }
            $resolved = realpath($root.'/docs/user-guide/'.dirname($file).'/'.explode('#', $href, 2)[0]);
            if (! $resolved) {
                throw new RuntimeException('Tautan publikasi gagal: '.$href);
            }
            $relative = substr(str_replace('\\', '/', $resolved), strlen($root.'/docs/user-guide/'));
            $url = isset($chapterIds[$relative]) ? '#'.$chapterIds[$relative]
                : 'https://github.com/AnggonoUtomo/pos/blob/main/'.substr(str_replace('\\', '/', $resolved), strlen($root) + 1);
            $anchor->setAttribute('href', $url);
        }
        $body = $document->getElementsByTagName('body')->item(0);
        $html = '';
        foreach ($body->childNodes as $node) {
            $html .= $document->saveHTML($node);
        }
        $chapters[] = '<section class="chapter">'.$html.'</section>';
    }
    $cover = '<header class="cover"><p class="eyebrow">POS MODULAR ERP-LITE / TOKO CCTV &amp; KELISTRIKAN</p><h1>'.$escape($title).'</h1>'
        .'<p class="subtitle">'.($global ? 'Dari persiapan toko sampai pemeriksaan akhir hari' : 'Lembar kerja bertahap sesuai tanggung jawab').'</p>'
        .'<p class="edition">Edisi 27 Oktober 2026</p><p class="notice">Panduan fitur yang tersedia. Role tambahan berupa rekomendasi, bukan perubahan akses database. Latihan hanya di lingkungan pelatihan.</p>'
        .($global ? '<div class="route">PERSIAPAN &gt; MASTER &gt; STOK &gt; JUAL/BELI &gt; RETUR &gt; PEMERIKSAAN</div>' : '').'</header>';
    $toc = '<nav class="toc"><h2>Daftar Isi</h2><p>Klik judul untuk berpindah. Gunakan pencarian pembaca PDF untuk menemukan istilah.</p><ul>'.implode('', $contents).'</ul></nav>';
    $html = '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>'.$escape($title).'</title><style>'.$css.'</style></head>'
        .'<body class="'.($global ? 'global' : 'role').'">'.$cover.$toc.'<main>'.implode('', $chapters).'</main></body></html>';
    $htmlPath = $temporary.'/'.$name.'.html';
    file_put_contents($htmlPath, $html);
    $manifest['publications'][] = ['name' => $name, 'title' => $title, 'sources' => $files, 'html' => $htmlPath, 'pdf' => $output.'/'.$name.'.pdf'];
}

file_put_contents($temporary.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);
echo 'PASS: '.count($documents).' Markdown, '.$links.' tautan lokal, '.count($permissions).' permission; 8 HTML siap dicetak.'.PHP_EOL;
