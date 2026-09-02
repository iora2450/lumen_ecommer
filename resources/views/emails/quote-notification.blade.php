<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nueva {{ strtolower($quote->request_type_label) }} {{ $quote->quote_number }}</title>
</head>
<body style="margin:0;background:#f4f7f8;font-family:Poppins,Arial,sans-serif;color:#203749;">
    <div style="max-width:720px;margin:0 auto;padding:28px 16px;">
        <div style="background:#203749;color:white;padding:24px;border-radius:12px 12px 0 0;">
            <p style="margin:0;color:#FFAE00;font-size:13px;font-weight:700;text-transform:uppercase;">Lumens</p>
            <h1 style="margin:8px 0 0;font-size:24px;">Nueva {{ strtolower($quote->request_type_label) }} recibida</h1>
            <p style="margin:8px 0 0;color:#dbe5e9;">{{ $quote->quote_number }}</p>
        </div>

        <div style="background:white;border:1px solid #C8D3D7;border-top:0;padding:24px;border-radius:0 0 12px 12px;">
            <h2 style="margin:0 0 12px;font-size:18px;">Datos del cliente</h2>
            <table style="width:100%;border-collapse:collapse;font-size:14px;margin-bottom:22px;">
                <tr>
                    <td style="padding:6px 0;color:#6d7d86;width:150px;">Nombre</td>
                    <td style="padding:6px 0;"><strong>{{ $quote->customer_name }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:6px 0;color:#6d7d86;">Correo</td>
                    <td style="padding:6px 0;"><a href="mailto:{{ $quote->customer_email }}" style="color:#203749;">{{ $quote->customer_email }}</a></td>
                </tr>
                @if ($quote->customer_phone)
                    <tr>
                        <td style="padding:6px 0;color:#6d7d86;">Telefono</td>
                        <td style="padding:6px 0;">{{ $quote->customer_phone }}</td>
                    </tr>
                @endif
                @if ($quote->customer_company)
                    <tr>
                        <td style="padding:6px 0;color:#6d7d86;">Empresa</td>
                        <td style="padding:6px 0;">{{ $quote->customer_company }}</td>
                    </tr>
                @endif
                @if ($quote->shipping_address)
                    <tr>
                        <td style="padding:6px 0;color:#6d7d86;">Direccion</td>
                        <td style="padding:6px 0;">{{ $quote->shipping_address }}</td>
                    </tr>
                @endif
            </table>

            <h2 style="margin:0 0 12px;font-size:18px;">Productos solicitados</h2>
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <thead>
                    <tr>
                        <th align="left" style="border-bottom:1px solid #C8D3D7;padding:10px 0;">Producto</th>
                        <th align="center" style="border-bottom:1px solid #C8D3D7;padding:10px 0;">Cant.</th>
                        <th align="right" style="border-bottom:1px solid #C8D3D7;padding:10px 0;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quote->items as $item)
                        <tr>
                            <td style="border-bottom:1px solid #eef2f3;padding:10px 0;">
                                <strong>{{ $item->name }}</strong><br>
                                <span style="font-size:12px;color:#6d7d86;">{{ $item->sku }}</span>
                            </td>
                            <td align="center" style="border-bottom:1px solid #eef2f3;padding:10px 0;">{{ $item->qty }}</td>
                            <td align="right" style="border-bottom:1px solid #eef2f3;padding:10px 0;">${{ number_format((float) $item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p style="margin:18px 0 0;text-align:right;font-size:18px;font-weight:800;">
                Total estimado: ${{ number_format((float) $quote->subtotal, 2) }}
            </p>

            @if ($quote->notes)
                <div style="margin-top:18px;padding:14px;background:#f4f7f8;border-radius:8px;">
                    <strong>Notas del cliente:</strong>
                    <p style="margin:6px 0 0;color:#4f6675;">{{ $quote->notes }}</p>
                </div>
            @endif

            <p style="margin:22px 0 0;">
                <a href="{{ route('admin.quotes.show', $quote) }}" style="display:inline-block;background:#FFAE00;color:#203749;text-decoration:none;font-weight:700;padding:12px 16px;border-radius:8px;">
                    Ver en panel admin
                </a>
            </p>
        </div>
    </div>
</body>
</html>
