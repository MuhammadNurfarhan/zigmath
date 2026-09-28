@if (\App\Models\Setting::get('invoice_show_logo', true) && file_exists(public_path('images/zigmath-logo.png')))
    <img src="{{ public_path('images/zigmath-logo.png') }}" alt="Zigmath" style="height: 60px; margin-bottom: 10px;">
@endif
