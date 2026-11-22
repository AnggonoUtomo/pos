using Pos.Setup;

internal static class InstallationSafetyTests
{
    private static int passed;

    public static int Main()
    {
        var root = Path.Combine(Path.GetTempPath(), "pos-deployment-test-" + Guid.NewGuid().ToString("N"));
        Directory.CreateDirectory(root);
        try
        {
            var program = Path.Combine(root, "Program Files", "POS");
            var data = Path.Combine(root, "ProgramData", "POS");
            PreflightInput Input() => new(program, data, 8443, 3307, true, true, [], [], 0);
            Check("empty paths accepted without creating them", () =>
            {
                Require(RuntimeConfiguration.Check(Input()).Count == 0);
                Require(!Directory.Exists(program) && !Directory.Exists(data));
            });
            Check("non-admin and unsupported OS rejected", () =>
                Require(RuntimeConfiguration.Check(Input() with { IsAdministrator = false, IsSupportedWindows = false }).Count == 2));
            Check("invalid, same and busy ports rejected", () =>
            {
                Require(RuntimeConfiguration.Check(Input() with { HttpsPort = 0 }).Count > 0);
                Require(RuntimeConfiguration.Check(Input() with { DatabasePort = 8443 }).Count > 0);
                Require(RuntimeConfiguration.Check(Input() with { BusyPorts = [8443] }).Count > 0);
            });
            Check("existing service cannot be taken over", () =>
                Require(RuntimeConfiguration.Check(Input() with { ExistingServices = ["posstandaloneweb"] }).Count > 0));
            Check("a file cannot be used as a parent directory", () =>
            {
                var file = Path.Combine(root, "not-a-directory");
                File.WriteAllText(file, "keep");
                Require(RuntimeConfiguration.Check(Input() with { DataPath = Path.Combine(file, "data") }).Count > 0);
                Require(File.ReadAllText(file) == "keep");
            });
            Check("nested or relative destinations rejected", () =>
            {
                Require(RuntimeConfiguration.Check(Input() with { DataPath = Path.Combine(program, "data") }).Count > 0);
                Require(RuntimeConfiguration.Check(Input() with { DataPath = "relative" }).Count > 0);
                Require(RuntimeConfiguration.Check(Input() with { DataPath = Path.GetPathRoot(data)! }).Count > 0);
                Require(RuntimeConfiguration.Check(Input() with { DataPath = @"\\server\share\POS" }).Count > 0);
            });
            Check("existing data and destination files stay untouched", () =>
            {
                Directory.CreateDirectory(data);
                var sentinel = Path.Combine(data, "keep.txt");
                File.WriteAllText(sentinel, "keep");
                Require(RuntimeConfiguration.Check(Input()).Count > 0);
                Require(File.ReadAllText(sentinel) == "keep");
                File.WriteAllText(Path.Combine(root, "file"), "keep");
                Require(RuntimeConfiguration.Check(Input() with { ProgramPath = Path.Combine(root, "file") }).Count > 0);
            });
            PayloadTests.Run(root);
            Console.WriteLine($"PASS: {passed} kasus; tidak ada service/database/registry yang diubah.");
            return 0;
        }
        catch (Exception error)
        {
            Console.Error.WriteLine("FAIL: " + error.Message);
            return 1;
        }
        finally
        {
            // Only remove the random directory created by this test process.
            if (Path.GetFileName(root).StartsWith("pos-deployment-test-", StringComparison.Ordinal))
                Directory.Delete(root, true);
        }
    }

    internal static void Check(string name, Action test)
    {
        try { test(); passed++; }
        catch (Exception error) { throw new InvalidOperationException(name + ": " + error.Message, error); }
    }

    internal static void Require(bool condition)
    {
        if (!condition) throw new InvalidOperationException("assertion failed");
    }

    internal static void Reject(Action action)
    {
        try { action(); }
        catch (InvalidDataException) { return; }
        throw new InvalidOperationException("unsafe input was accepted");
    }
}
