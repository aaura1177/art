<style>
    .supplier-terms-document {
        max-width: 1200px;
        width: 100%;
        margin: 0 auto;
        font-family: Georgia, 'Times New Roman', Times, serif;
        font-size: 1.05rem;
        line-height: 1.65;
        color: #1a1a1a;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
    .supplier-terms-document .card {
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }
    .supplier-terms-document .card-header {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        padding: 1.25rem 1.5rem;
    }
    .supplier-terms-document .card-header h1 {
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        margin: 0;
        text-align: center;
        font-family: inherit;
    }
    .supplier-terms-document .card-body {
        padding: 2rem 2.25rem 2.5rem;
    }
    .supplier-terms-document--embedded {
        max-width: none;
        margin: 0;
    }
    .supplier-terms-document--embedded .card {
        border: none;
        box-shadow: none;
    }
    .supplier-terms-document--embedded .card-body {
        padding: 0;
    }
    .supplier-terms-document .terms-rendered,
    .supplier-terms-document .terms-stored-html {
        text-align: justify;
    }
    .supplier-terms-document .terms-stored-html p {
        margin: 0 0 0.85rem;
    }
    .supplier-terms-document .terms-stored-html p.terms-doc-header {
        text-align: center;
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #ccc;
        line-height: 1.75;
    }
    .supplier-terms-document .terms-stored-html p.terms-doc-header strong {
        display: block;
        margin-bottom: 0.35rem;
    }
    .supplier-terms-document .terms-stored-html h2.terms-doc-section {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 1.5rem 0 0.65rem;
        padding-bottom: 0.35rem;
        border-bottom: 1px solid #e0e0e0;
        text-align: left;
        text-transform: none;
    }
    .supplier-terms-document .terms-section {
        text-align: justify;
        hyphens: auto;
        margin-bottom: 0;
    }
    .supplier-terms-document .terms-section--lead {
        text-align: center;
        font-size: 1rem;
        line-height: 1.75;
        margin-bottom: 1.75rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #ccc;
    }
    .supplier-terms-document .terms-separator {
        border: 0;
        height: 1px;
        background: linear-gradient(to right, transparent, #bbb 20%, #bbb 80%, transparent);
        margin: 2rem 0;
    }
    .supplier-terms-document h1,
    .supplier-terms-document h2,
    .supplier-terms-document h3,
    .supplier-terms-document h4 {
        font-family: inherit;
        font-weight: 700;
        margin-top: 1.25rem;
        margin-bottom: 0.65rem;
        text-align: left;
        text-transform: none;
    }
    .supplier-terms-document h1 { font-size: 1.35rem; }
    .supplier-terms-document h2 { font-size: 1.2rem; }
    .supplier-terms-document h3 { font-size: 1.08rem; }
    .supplier-terms-document h4 { font-size: 1.02rem; }
    .supplier-terms-document p { margin: 0 0 0.85rem; }
    .supplier-terms-document ul,
    .supplier-terms-document ol {
        margin: 0 0 1rem 1.25rem;
        padding-left: 0.25rem;
    }
    .supplier-terms-document li { margin-bottom: 0.35rem; }
    .supplier-terms-document hr {
        border: 0;
        height: 1px;
        background: #ccc;
        margin: 1.5rem 0;
    }
    .supplier-terms-document strong,
    .supplier-terms-document b { font-weight: 700; }
    .supplier-terms-document .terms-stored-html table {
        width: 100%;
        max-width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
        font-size: 0.98rem;
        table-layout: fixed;
    }
    .supplier-terms-document .terms-stored-html th,
    .supplier-terms-document .terms-stored-html td {
        border: 1px solid #ccc;
        padding: 0.5rem 0.65rem;
        vertical-align: top;
        word-wrap: break-word;
    }
    .supplier-terms-document .terms-stored-html th {
        background: #f5f5f5;
        font-weight: 700;
    }
    .supplier-terms-document .terms-stored-html blockquote {
        margin: 1rem 0;
        padding: 0.5rem 1rem;
        border-left: 3px solid #ccc;
    }
    #supplierTermsAcceptModal .modal-body {
        max-height: 65vh;
        overflow-y: auto;
        overflow-x: hidden;
    }
    #supplierTermsAcceptModal .supplier-terms-document .card-body {
        padding: 0;
    }
    body.supplier-terms-dashboard-blocked #wrapper {
        pointer-events: none;
        user-select: none;
        filter: grayscale(0.15);
    }
    body.supplier-terms-dashboard-blocked .modal-backdrop {
        pointer-events: auto;
        z-index: 1050;
    }
    body.supplier-terms-dashboard-blocked #supplierTermsAcceptModal {
        pointer-events: auto;
        z-index: 1060;
    }
</style>
