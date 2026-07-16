# Ticket History Implementation Planning

## Tujuan
Modul **Ticket History** digunakan untuk mencatat seluruh aktivitas penting pada ticket sebagai audit trail. Data hanya di-INSERT dan dibaca, tidak diubah atau dihapus.

## Struktur Tabel
| Field | Tipe | Keterangan |
|---|---|---|
| id | UUID | PK |
| ticket_id | UUID | FK tickets |
| user_id | UUID | FK users |
| action | VARCHAR(100) | Jenis aktivitas |
| field_name | VARCHAR(100) | Field yang berubah |
| old_value | TEXT | Nilai lama |
| new_value | TEXT | Nilai baru |
| description | TEXT | Deskripsi |
| created_at | TIMESTAMP | Waktu aktivitas |

## Action
- TICKET_CREATED
- ASSIGNED_AGENT
- REASSIGNED_AGENT
- STATUS_CHANGED
- PRIORITY_CHANGED
- CATEGORY_CHANGED
- COMMENT_ADDED
- ATTACHMENT_UPLOADED
- TICKET_RESOLVED
- TICKET_CLOSED
- TICKET_REOPENED

## Aturan Implementasi
- Setiap perubahan ticket harus membuat satu record history.
- Gunakan transaksi database agar update ticket dan insert history bersifat atomik.
- Jangan pernah UPDATE atau DELETE data history.
- Timeline ditampilkan berdasarkan created_at ASC.
- Timeline UI dapat menggabungkan ticket_histories dan ticket_comments.

## Checklist
- [ ] Migration
- [ ] Model
- [ ] Enum Action
- [ ] History Service
- [ ] Integrasi Create Ticket
- [ ] Integrasi Assignment
- [ ] Integrasi Status
- [ ] Integrasi Priority
- [ ] Integrasi Category
- [ ] Integrasi Attachment
- [ ] Integrasi Resolve
- [ ] Integrasi Close
- [ ] Endpoint Timeline
- [ ] Unit Test
