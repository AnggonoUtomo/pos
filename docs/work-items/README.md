# Work Item Lintas Module

Folder ini hanya untuk pekerjaan signifikan yang dimiliki lebih dari satu module
atau menyentuh framework dan beberapa module sekaligus.

```text
docs/work-items/{nama-pekerjaan}/
|-- README.md
|-- plan.md
`-- tasks.md
```

Gunakan nama folder `kebab-case`. Sebutkan owner setiap perubahan dan dependency
antar increment.

Jika pekerjaan hanya dimiliki satu module, simpan di:

```text
docs/modules/{Domain}/{Module}/work-items/{nama-pekerjaan}/
```

## Checklist Wajib

Setiap work item signifikan harus memuat:

- scope dan non-scope;
- acceptance criteria;
- daftar increment;
- checklist sebelum coding;
- checklist sesudah coding;
- command QA automated;
- catatan Chrome DevTools MCP bila UI/browser tersentuh;
- catatan gap conformance bila ada.

Jangan menambahkan pekerjaan baru ke work item berjalan tanpa persetujuan user.
