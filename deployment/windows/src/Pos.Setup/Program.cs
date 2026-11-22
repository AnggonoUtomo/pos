using System.Text.Json;

namespace Pos.Setup;

internal static class Program
{
    [STAThread]
    public static int Main(string[] args)
    {
        try
        {
            if (args.Length == 3 && args[0] == "--verify-payload")
            {
                Console.WriteLine(JsonSerializer.Serialize(new { status = "PASS", files = PayloadVerifier.Verify(args[1], args[2]), kind = "application-only" }));
                return 0;
            }
            if (args.Length == 1 && args[0] == "--preflight-defaults")
            {
                var issues = SetupWizard.CheckDefaults();
                Console.WriteLine(JsonSerializer.Serialize(new { status = issues.Count == 0 ? "PASS" : "BLOCKED", issues,
                    installationPerformed = false, databaseConnectionVerified = false }));
                return issues.Count == 0 ? 0 : 2;
            }
            if (args.Length > 0) throw new InvalidDataException("Argumen tidak dikenal.");
            ApplicationConfiguration.Initialize();
            Application.Run(new SetupWizard());
            return 0;
        }
        catch (Exception error) when (error is IOException or InvalidDataException or UnauthorizedAccessException or ArgumentException)
        {
            Console.Error.WriteLine("Pemeriksaan ditolak: " + error.Message);
            return 1;
        }
    }
}
