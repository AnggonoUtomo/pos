namespace Pos.Setup;

public sealed record PreflightInput(string ProgramPath, string DataPath, int HttpsPort, int DatabasePort,
    bool IsSupportedWindows, bool IsAdministrator, string[] ExistingServices, int[] BusyPorts, long RequiredFreeBytes);

public static class RuntimeConfiguration
{
    public static readonly string[] ServiceNames = ["PosStandaloneDatabase", "PosStandaloneWeb", "PosStandaloneScheduler"];

    public static List<string> Check(PreflightInput input)
    {
        var errors = new List<string>();
        if (!input.IsSupportedWindows) errors.Add("Target rilis awal hanya Windows 11 x64; OS ini belum didukung.");
        if (!input.IsAdministrator) errors.Add("Instalasi penuh nantinya memerlukan persetujuan administrator (UAC).");
        if (input.HttpsPort is < 1024 or > 65535 || input.DatabasePort is < 1024 or > 65535 || input.HttpsPort == input.DatabasePort)
            errors.Add("Pilih dua port berbeda dalam rentang 1024-65535.");
        if (input.BusyPorts.Contains(input.HttpsPort) || input.BusyPorts.Contains(input.DatabasePort))
            errors.Add("Port pilihan sudah dipakai. Tidak ada proses yang dihentikan.");
        if (input.ExistingServices.Intersect(ServiceNames, StringComparer.OrdinalIgnoreCase).Any())
            errors.Add("Nama service POS sudah ada. Gunakan alur repair, bukan mengambil alih service.");
        var program = CheckPath(input.ProgramPath, input.RequiredFreeBytes, errors);
        var data = CheckPath(input.DataPath, input.RequiredFreeBytes, errors);
        if (program is not null && data is not null && (Within(program, data) || Within(data, program)))
            errors.Add("Folder aplikasi dan data harus terpisah, tidak boleh saling berada di dalamnya.");
        return errors;
    }

    private static bool Within(string candidate, string parent) => candidate.Equals(parent, StringComparison.OrdinalIgnoreCase) ||
        candidate.StartsWith(parent + Path.DirectorySeparatorChar, StringComparison.OrdinalIgnoreCase);

    private static string? CheckPath(string path, long requiredBytes, List<string> errors)
    {
        try
        {
            if (string.IsNullOrWhiteSpace(path) || !Path.IsPathFullyQualified(path) || path.StartsWith(@"\\", StringComparison.Ordinal) ||
                path.IndexOf(':', 2) >= 0 || path.Split(Path.DirectorySeparatorChar, Path.AltDirectorySeparatorChar).Skip(1)
                    .Any(part => part is "." or ".." || part.EndsWith('.') || part.EndsWith(' ')))
                throw new InvalidDataException("Gunakan path lokal absolut tanpa traversal atau alternate data stream.");
            var full = Path.TrimEndingDirectorySeparator(Path.GetFullPath(path));
            if (full == Path.TrimEndingDirectorySeparator(Path.GetPathRoot(full)!))
                throw new InvalidDataException("Root drive bukan tujuan instalasi.");
            PayloadVerifier.AssertNoReparsePoints(full);
            for (var parent = Path.GetDirectoryName(full); !string.IsNullOrEmpty(parent); parent = Path.GetDirectoryName(parent))
                if (File.Exists(parent)) throw new InvalidDataException("Parent tujuan adalah file, bukan folder.");
            if (File.Exists(full) || (Directory.Exists(full) && Directory.EnumerateFileSystemEntries(full).Any()))
                throw new InvalidDataException("Tujuan sudah berisi file/data. Tidak ada yang ditimpa.");
            var drive = new DriveInfo(Path.GetPathRoot(full)!);
            if (!drive.IsReady || drive.DriveType != DriveType.Fixed || (requiredBytes > 0 && drive.AvailableFreeSpace < requiredBytes))
                throw new InvalidDataException("Drive harus lokal/fixed, tersedia dan memiliki ruang cukup.");
            return full;
        }
        catch (Exception error) when (error is IOException or InvalidDataException or ArgumentException or UnauthorizedAccessException or NotSupportedException)
        {
            errors.Add(error.Message);
            return null;
        }
    }
}
