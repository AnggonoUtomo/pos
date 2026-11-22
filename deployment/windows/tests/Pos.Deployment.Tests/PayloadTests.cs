using System.Security.Cryptography;
using System.Text.Json;
using Pos.Setup;
using static InstallationSafetyTests;

internal static class PayloadTests
{
    internal static void Run(string temporaryRoot)
    {
        foreach (var path in new[] { "../escape", "app/../escape", "/root", "C:/escape", "app\\escape",
            "app//file", "app/file.", "app/file ", "app/CON.txt", "app/.env", "public/.env.backup",
            "storage/app/private/file", "backups/a.sql", "public/storage/photo.jpg", "public/hot",
            "bootstrap/cache/config.php", "app/private.key", "app/test.sql", "tests/a.php",
            "Old-docs/a.md", "node_modules/a.js", "vendor/a/.git/config", "app/hardware-drivers/a.exe" })
        {
            var captured = path;
            Check("reject path " + path, () => Reject(() => PayloadVerifier.ValidatePath(captured)));
        }
        foreach (var path in new[] { "artisan", "app/Modules/Inventory/Items/ServiceProvider.php",
            "public/build/assets/app-test.js", "vendor/composer/installed.json", "public/.htaccess" })
        {
            var captured = path;
            Check("allow path " + path, () => PayloadVerifier.ValidatePath(captured));
        }

        var payload = Path.Combine(temporaryRoot, "payload");
        Directory.CreateDirectory(payload);
        var names = new[] { "artisan", "bootstrap/app.php", "composer.json", "composer.lock",
            "vendor/autoload.php", "public/build/manifest.json" };
        foreach (var name in names)
        {
            var target = Path.Combine(payload, name);
            Directory.CreateDirectory(Path.GetDirectoryName(target)!);
            File.WriteAllText(target, "fixture");
        }
        var manifest = Path.Combine(temporaryRoot, "payload-manifest.json");
        var entries = names.Select(name => new PayloadFile(name, new FileInfo(Path.Combine(payload, name)).Length,
            Convert.ToHexString(SHA256.HashData(File.ReadAllBytes(Path.Combine(payload, name)))).ToLowerInvariant())).ToArray();
        void Write(PayloadFile[] files) => File.WriteAllText(manifest,
            JsonSerializer.Serialize(new PayloadManifest(1, "application-only", new string('a', 40), files), PayloadVerifier.JsonOptions));
        Write(entries);
        Check("intact payload accepted", () => Require(PayloadVerifier.Verify(payload, manifest) == names.Length));
        Check("empty private directory also rejected", () =>
        {
            var forbidden = Path.Combine(payload, "public", "storage");
            Directory.CreateDirectory(forbidden);
            Reject(() => PayloadVerifier.Verify(payload, manifest));
            Directory.Delete(forbidden);
        });
        Check("tampering rejected", () =>
        {
            File.WriteAllText(Path.Combine(payload, "artisan"), "tampered");
            Reject(() => PayloadVerifier.Verify(payload, manifest));
            File.WriteAllText(Path.Combine(payload, "artisan"), "fixture");
        });
        Check("unlisted extra file rejected", () =>
        {
            var extra = Path.Combine(payload, "app.php");
            File.WriteAllText(extra, "extra");
            Reject(() => PayloadVerifier.Verify(payload, manifest));
            File.Delete(extra);
        });
        Check("duplicate case-insensitive path rejected", () =>
        {
            Write([.. entries, entries[0] with { Path = "ARTISAN" }]);
            Reject(() => PayloadVerifier.Verify(payload, manifest));
            Write(entries);
        });
        Check("manifest cannot whitelist private content", () =>
        {
            Write([.. entries, new(".env", 0, new string('a', 64))]);
            Reject(() => PayloadVerifier.Verify(payload, manifest));
            Write(entries);
        });
        Check("required runtime files cannot be omitted", () =>
        {
            Write(entries.Where(file => file.Path != "vendor/autoload.php").ToArray());
            Reject(() => PayloadVerifier.Verify(payload, manifest));
            Write(entries);
        });
        Check("missing payload file rejected", () =>
        {
            File.Delete(Path.Combine(payload, "artisan"));
            Reject(() => PayloadVerifier.Verify(payload, manifest));
        });
    }
}
