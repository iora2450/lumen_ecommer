@extends('layouts.admin')
@section('title', 'Tema y Contacto')
@section('subtitle', 'Ajustes rápidos de identidad visual y datos visibles en la web.')

@section('content')
<form method="POST" action="{{ route('admin.theme.update') }}" class="grid gap-6 lg:grid-cols-[1fr_360px]">
    @csrf
    @method('PATCH')

    <div class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Identidad visual</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-1 text-sm font-semibold text-slate-700 md:col-span-2">
                    Nombre del sitio
                    <input name="site_name" value="{{ old('site_name', $settings['site_name']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700">
                    Color principal
                    <input type="color" name="primary_color" value="{{ old('primary_color', $settings['primary_color']) }}" class="h-12 w-full rounded-lg border border-slate-300 bg-white p-1">
                </label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700">
                    Color principal oscuro
                    <input type="color" name="primary_dark_color" value="{{ old('primary_dark_color', $settings['primary_dark_color']) }}" class="h-12 w-full rounded-lg border border-slate-300 bg-white p-1">
                </label>
                <label class="grid gap-2 text-sm font-semibold text-slate-700">
                    Color de acento
                    <input type="color" name="accent_color" value="{{ old('accent_color', $settings['accent_color']) }}" class="h-12 w-full rounded-lg border border-slate-300 bg-white p-1">
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Inicio</h2>
            <div class="grid gap-4">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Título principal
                    <input name="home_hero_title" value="{{ old('home_hero_title', $settings['home_hero_title']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Texto principal
                    <textarea name="home_hero_subtitle" rows="3" class="rounded-lg border border-slate-300 px-3 py-2 font-normal leading-relaxed focus:border-[#FFAE00] focus:outline-none">{{ old('home_hero_subtitle', $settings['home_hero_subtitle']) }}</textarea>
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Contacto</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Correo
                    <input name="footer_email" value="{{ old('footer_email', $settings['footer_email']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Teléfono
                    <input name="footer_phone" value="{{ old('footer_phone', $settings['footer_phone']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Ubicación
                    <input name="footer_location" value="{{ old('footer_location', $settings['footer_location']) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
            </div>
        </section>
    </div>

    <aside class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Vista rápida</h2>
            <div class="overflow-hidden rounded-xl border border-slate-200">
                <div class="p-5 text-white" style="background: linear-gradient(135deg, {{ old('primary_color', $settings['primary_color']) }}, {{ old('primary_dark_color', $settings['primary_dark_color']) }});">
                    <p class="text-xs font-bold uppercase tracking-wide" style="color: {{ old('accent_color', $settings['accent_color']) }}">Lumens</p>
                    <h3 class="mt-2 text-xl font-extrabold">{{ old('home_hero_title', $settings['home_hero_title']) }}</h3>
                    <p class="mt-2 text-sm text-white/80">{{ old('home_hero_subtitle', $settings['home_hero_subtitle']) }}</p>
                    <span class="mt-4 inline-flex rounded-full px-4 py-2 text-sm font-bold" style="background: {{ old('accent_color', $settings['accent_color']) }}; color: {{ old('primary_color', $settings['primary_color']) }};">Cotizar</span>
                </div>
            </div>
        </section>

        <button class="w-full rounded-lg bg-[#203749] px-5 py-3 text-sm font-bold text-white">Guardar tema</button>
    </aside>
</form>
@endsection
