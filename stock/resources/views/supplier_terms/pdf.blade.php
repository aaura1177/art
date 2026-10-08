<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10.5pt;
            line-height: 1.5;
            color: #1a1a1a;
            margin: 28px 32px;
        }
        .pdf-doc-title {
            font-size: 13pt;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            margin: 0 0 18px;
        }
        .terms-stored-html p {
            margin: 0 0 8px;
            text-align: justify;
        }
        .terms-stored-html p.terms-doc-header {
            text-align: center;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #999;
            line-height: 1.55;
        }
        .terms-stored-html p.terms-doc-header strong {
            display: block;
            margin-bottom: 4px;
        }
        .terms-stored-html h2.terms-doc-section {
            font-size: 11.5pt;
            font-weight: bold;
            margin: 14px 0 6px;
            padding-bottom: 3px;
            border-bottom: 1px solid #ccc;
            page-break-after: avoid;
        }
        .terms-stored-html h1,
        .terms-stored-html h2,
        .terms-stored-html h3,
        .terms-stored-html h4 {
            font-weight: bold;
            margin: 12px 0 6px;
            page-break-after: avoid;
        }
        .terms-stored-html strong,
        .terms-stored-html b { font-weight: bold; }
        .terms-stored-html table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10pt;
        }
        .terms-stored-html th,
        .terms-stored-html td {
            border: 1px solid #666;
            padding: 5px 6px;
            vertical-align: top;
        }
        .terms-stored-html th {
            background: #eee;
            font-weight: bold;
        }
        .terms-stored-html ul,
        .terms-stored-html ol {
            margin: 0 0 8px 16px;
            padding: 0;
        }
        .terms-stored-html li { margin-bottom: 3px; }
        .terms-stored-html blockquote {
            margin: 8px 0;
            padding-left: 10px;
            border-left: 2px solid #999;
        }
    </style>
</head>
<body>
    <div class="pdf-doc-title">{{ $title }}</div>
    {!! $bodyHtml !!}
</body>
</html>
