using System.Security.Cryptography;
using System.Text.Json;
using System.Text.RegularExpressions;

namespace Pos.Setup;

public sealed record PayloadFile(string Path, long Length, string Sha256);
public sealed record PayloadManifest(int SchemaVersion, string Kind, string SourceCommit, PayloadFile[] Files);

public static class PayloadVerifier
{
    public static readonly JsonSerializerOptions JsonOptions = new() { PropertyNamingPolicy = JsonNamingPolicy.CamelCase };
    private static readonly string[] Roots = ["app", "bootstrap", "config", "database", "public", "resources", "routes", "vendor"];
    private static readonly string[] RootFiles = ["artisan", "composer.json", "composer.lock"];
    private static readonly string[] PrivateSegments = [".git", "node_modules", "tests", "Old-docs", "hardware-drivers", "backups"];
    private static readonly string[] Required = ["artisan", "composer.json", "composer.lock", "bootstrap/app.php", "vendor/autoload.php", "public/build/manifest.json"];

    public static void ValidatePath(string path)
    {
        if (string.IsNullOrEmpty(path) || path.Contains('\\') || path.Contains(':') || path.Any(char.IsControl))
            throw new InvalidDataException("Path payload tidak valid.");
        var parts = path.Split('/');
        if (parts.Any(part => part is "" or "." or ".." || part.EndsWith('.') || part.EndsWith(' ') ||
            part.IndexOfAny(['<', '>', '"', '|', '?', '*']) >= 0 ||
            Regex.IsMatch(part, @"^(CON|PRN|AUX|NUL|COM[0-9]|LPT[0-9])($|\.)", RegexOptions.IgnoreCase | RegexOptions.CultureInvariant)))
            throw new InvalidDataException("Path traversal atau nama Windows terlarang.");
        if (!(parts.Length == 1 && RootFiles.Contains(path, StringComparer.OrdinalIgnoreCase)) &&
            !(parts.Length > 1 && Roots.Contains(parts[0], StringComparer.OrdinalIgnoreCase)))
            throw new InvalidDataException("File di luar allowlist aplikasi.");
        if (parts.Any(part => PrivateSegments.Contains(part, StringComparer.OrdinalIgnoreCase) ||
            part.StartsWith(".env", StringComparison.OrdinalIgnoreCase)) ||
            Regex.IsMatch(path, @"\.(sql|sqlite|sqlite3|key|pem|pfx|p12|bak)$", RegexOptions.IgnoreCase | RegexOptions.CultureInvariant) ||
            path.StartsWith("public/storage/", StringComparison.OrdinalIgnoreCase) ||
            path.Equals("public/hot", StringComparison.OrdinalIgnoreCase) ||
            path.StartsWith("bootstrap/cache/", StringComparison.OrdinalIgnoreCase) ||
            path.Contains("/lsp-", StringComparison.OrdinalIgnoreCase))
            throw new InvalidDataException("Data privat/cache tidak boleh masuk payload.");
    }

    public static void AssertNoReparsePoints(string path)
    {
        for (var current = Path.GetFullPath(path); !string.IsNullOrEmpty(current); current = Path.GetDirectoryName(current))
        {
            if ((File.Exists(current) || Directory.Exists(current)) &&
                (File.GetAttributes(current) & FileAttributes.ReparsePoint) != 0)
                throw new InvalidDataException("Symlink/junction tidak diizinkan.");
        }
    }

    public static int Verify(string root, string manifestPath)
    {
        root = Path.GetFullPath(root);
        AssertNoReparsePoints(root);
        AssertNoReparsePoints(manifestPath);
        if (!Directory.Exists(root)) throw new InvalidDataException("Payload tidak ditemukan.");
        PayloadManifest manifest;
        try
        {
            manifest = JsonSerializer.Deserialize<PayloadManifest>(File.ReadAllText(manifestPath), JsonOptions)
                ?? throw new InvalidDataException("Manifest kosong.");
        }
        catch (JsonException error) { throw new InvalidDataException("Manifest tidak valid.", error); }
        if (manifest.SchemaVersion != 1 || manifest.Kind != "application-only" || manifest.Files is null ||
            !Regex.IsMatch(manifest.SourceCommit ?? "", "^[a-f0-9]{40}$"))
            throw new InvalidDataException("Schema/kind/commit manifest tidak valid.");

        var expected = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
        foreach (var entry in manifest.Files)
        {
            if (entry is null) throw new InvalidDataException("Entry manifest kosong.");
            ValidatePath(entry.Path);
            if (!expected.Add(entry.Path) || entry.Length < 0 || !Regex.IsMatch(entry.Sha256 ?? "", "^[a-f0-9]{64}$"))
                throw new InvalidDataException("Duplicate path/ukuran/hash tidak valid.");
        }
        if (Required.Any(path => !expected.Contains(path))) throw new InvalidDataException("File runtime wajib tidak lengkap.");

        var actual = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
        var pending = new Stack<string>();
        pending.Push(root);
        while (pending.Count > 0)
        {
            foreach (var item in Directory.EnumerateFileSystemEntries(pending.Pop()))
            {
                if ((File.GetAttributes(item) & FileAttributes.ReparsePoint) != 0)
                    throw new InvalidDataException("Symlink/junction payload ditolak.");
                var relative = Path.GetRelativePath(root, item).Replace('\\', '/');
                if (Directory.Exists(item))
                {
                    ValidatePath(relative + "/__directory_check__");
                    pending.Push(item);
                    continue;
                }
                ValidatePath(relative);
                if (!actual.Add(relative) || !expected.Contains(relative))
                    throw new InvalidDataException("File tambahan/duplicate di luar manifest.");
            }
        }
        if (!actual.SetEquals(expected)) throw new InvalidDataException("File payload hilang.");
        foreach (var entry in manifest.Files)
        {
            var file = Path.Combine(root, entry.Path);
            using var stream = File.OpenRead(file);
            if (stream.Length != entry.Length || !Convert.ToHexString(SHA256.HashData(stream)).Equals(entry.Sha256, StringComparison.OrdinalIgnoreCase))
                throw new InvalidDataException("Ukuran/checksum payload berubah.");
        }
        return actual.Count;
    }
}
