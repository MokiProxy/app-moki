<style>
    .spph-hero {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        background: linear-gradient(135deg, #556ee6 0%, #2b3142 100%);
        color: #fff;
    }

    .spph-hero::after {
        content: "";
        position: absolute;
        right: -70px;
        top: -70px;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .spph-hero-icon {
        width: 72px;
        height: 72px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.25rem;
        background: rgba(255, 255, 255, 0.16);
        flex-shrink: 0;
    }

    .spph-menu-card {
        border: 1px solid #e9ecef;
        border-radius: 16px;
    }

    .spph-hoverable {
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .spph-hoverable:hover {
        transform: translateY(-5px);
        border-color: rgba(85, 110, 230, 0.35);
        box-shadow: 0 14px 28px rgba(43, 49, 66, 0.12) !important;
    }

    .spph-icon-soft {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .spph-icon-sm {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        font-size: 1.2rem;
    }

    .spph-empty .spph-icon-soft {
        width: 88px;
        height: 88px;
        border-radius: 24px;
    }

    .spph-form-card {
        border: 1px solid #eef0f4;
        border-radius: 14px;
        overflow: hidden;
    }

    .spph-form-card > .card-header {
        background: #f8f9fc;
        border-bottom: 1px solid #eef0f4;
    }

    .spph-form-card .form-label {
        margin-bottom: 0.25rem;
    }

    .spph-form-card .input-group-text {
        background: #f8f9fc;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .spph-konfigurasi-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        transition: all 0.25s ease;
        height: 100%;
    }
    .spph-konfigurasi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px rgba(43, 49, 66, 0.12);
        border-color: rgba(85, 110, 230, 0.35);
        text-decoration: none !important;
        color: inherit !important;
    }

    .bg-soft-primary { background: rgba(85, 110, 230, 0.12); color: #556ee6; }
    .bg-soft-success { background: rgba(54, 203, 131, 0.14); color: #2f9e6b; }
    .bg-soft-warning { background: rgba(241, 180, 76, 0.16); color: #d99b28; }
    .bg-soft-info { background: rgba(52, 195, 235, 0.16); color: #2aa9c9; }
    .bg-soft-danger { background: rgba(240, 101, 72, 0.14); color: #d9534f; }

    .text-dark { color: #1f2937 !important; }
</style>
