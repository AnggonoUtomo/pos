using System.Net.NetworkInformation;
using System.Security.Principal;
using Microsoft.Win32;

namespace Pos.Setup;

internal sealed class SetupWizard : Form
{
    public SetupWizard()
    {
        Text = "POS - Pemeriksaan awal (bukan installer produksi)";
        ClientSize = new Size(760, 480);
        MinimumSize = new Size(660, 400);
        StartPosition = FormStartPosition.CenterScreen;
        Font = new Font("Segoe UI", 10);
        var layout = new TableLayoutPanel { Dock = DockStyle.Fill, Padding = new Padding(24), RowCount = 4, ColumnCount = 1 };
        layout.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        layout.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        layout.RowStyles.Add(new RowStyle(SizeType.Percent, 100));
        layout.RowStyles.Add(new RowStyle(SizeType.AutoSize));
        layout.Controls.Add(new Label { AutoSize = true, Text = "Pemeriksaan kesiapan PC", Font = new Font(Font, FontStyle.Bold), Margin = new Padding(0, 0, 0, 14) });
        layout.Controls.Add(new Label { AutoSize = true, MaximumSize = new Size(680, 0), Text =
            "Alat internal ini hanya memeriksa port, folder dan nama service. Tidak memasang POS, mengubah data atau membuka jaringan.\n" +
            "Bundel MySQL untuk distribusi aplikasi tertutup masih menunggu kepastian lisensi.\n" +
            "Opsi server otomatis, LAN dan Scheduler belum diterapkan pada tahap ini.", Margin = new Padding(0, 0, 0, 16) });
        var output = new TextBox { ReadOnly = true, Multiline = true, ScrollBars = ScrollBars.Vertical, Dock = DockStyle.Fill,
            Text = "Klik Periksa untuk pemeriksaan read-only. Instalasi penuh nantinya memerlukan UAC administrator dan persetujuan lisensi." };
        layout.Controls.Add(output);
        var actions = new FlowLayoutPanel { AutoSize = true, Dock = DockStyle.Fill, FlowDirection = FlowDirection.RightToLeft, Padding = new Padding(0, 14, 0, 0) };
        var close = new Button { Text = "Tutup", AutoSize = true };
        close.Click += (_, _) => Close();
        var check = new Button { Text = "Periksa", AutoSize = true };
        check.Click += (_, _) =>
        {
            try
            {
                var issues = CheckDefaults();
                output.Text = issues.Count == 0 ? "Pemeriksaan awal lulus. Ini bukan bukti instalasi atau izin distribusi." : string.Join(Environment.NewLine + Environment.NewLine, issues);
            }
            catch (Exception error) when (error is IOException or UnauthorizedAccessException or System.Security.SecurityException or NetworkInformationException)
            {
                output.Text = "Pemeriksaan belum lengkap: " + error.Message;
            }
        };
        actions.Controls.Add(close);
        actions.Controls.Add(check);
        layout.Controls.Add(actions);
        Controls.Add(layout);
        CancelButton = close;
    }

    internal static List<string> CheckDefaults()
    {
        using var identity = WindowsIdentity.GetCurrent();
        using var servicesKey = Registry.LocalMachine.OpenSubKey(@"SYSTEM\CurrentControlSet\Services");
        var ports = IPGlobalProperties.GetIPGlobalProperties().GetActiveTcpListeners().Select(endpoint => endpoint.Port).ToArray();
        return RuntimeConfiguration.Check(new PreflightInput(
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.ProgramFiles), "POS"),
            Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.CommonApplicationData), "POS"),
            8443, 3307,
            System.Runtime.InteropServices.RuntimeInformation.OSArchitecture == System.Runtime.InteropServices.Architecture.X64 && Environment.OSVersion.Version.Build >= 22000,
            new WindowsPrincipal(identity).IsInRole(WindowsBuiltInRole.Administrator),
            servicesKey?.GetSubKeyNames() ?? throw new IOException("Daftar service tidak dapat dibaca; preflight tidak diluluskan."),
            ports, 0));
    }
}
