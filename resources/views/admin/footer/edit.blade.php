@extends('layouts.admin')
@section('title', 'Pie de página')
@section('subtitle', 'Administra la descripción, los datos de contacto y los horarios visibles al final del sitio.')

@section('content')
<form method="POST" action="{{ route('admin.footer.update') }}" class="grid gap-6 lg:grid-cols-[1fr_380px]">
    @csrf
    @method('PATCH')

    <div class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Información de Lumens</h2>
            <label class="grid gap-1 text-sm font-semibold text-slate-700">
                Descripción corta
                <textarea name="footer_description" rows="3" maxlength="240" required
                          class="rounded-lg border border-slate-300 px-3 py-2 font-normal leading-relaxed focus:border-[#FFAE00] focus:outline-none">{{ old('footer_description', $settings['footer_description']) }}</textarea>
                <span class="text-xs font-normal text-slate-500">Aparece debajo del nombre de la empresa.</span>
            </label>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Contacto</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Correo electrónico
                    <input type="email" name="footer_email" value="{{ old('footer_email', $settings['footer_email']) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Teléfono
                    <input name="footer_phone" value="{{ old('footer_phone', $settings['footer_phone']) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700 md:col-span-2">
                    Ubicación
                    <input name="footer_location" value="{{ old('footer_location', $settings['footer_location']) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Horarios</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Lunes a viernes
                    <input name="footer_weekday_hours" value="{{ old('footer_weekday_hours', $settings['footer_weekday_hours']) }}"
                           placeholder="Lun-Vie: 8:00am - 5:00pm"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Sábado
                    <input name="footer_saturday_hours" value="{{ old('footer_saturday_hours', $settings['footer_saturday_hours']) }}"
                           placeholder="Sáb: 9:00am - 1:00pm"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
            </div>
        </section>
    </div>

    <aside class="space-y-5 lg:sticky lg:top-6 lg:self-start">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-[#203749] p-5 text-white">
            <h2 class="mb-5 text-sm font-bold uppercase tracking-wide text-[#FFAE00]">Vista de referencia</h2>
            <div class="space-y-6 text-sm">
                <div>
                    <p class="text-xl font-bold">Lumens</p>
                    <p class="mt-2 text-slate-300">{{ old('footer_description', $settings['footer_description']) }}</p>
                </div>
                <div>
                    <p class="font-semibold">Contacto</p>
                    <div class="mt-2 space-y-1 text-slate-300">
                        <p>{{ old('footer_email', $settings['footer_email']) }}</p>
                        <p>{{ old('footer_phone', $settings['footer_phone']) }}</p>
                        <p>{{ old('footer_location', $settings['footer_location']) }}</p>
                    </div>
                </div>
                <div>
                    <p class="font-semibold">Horarios</p>
                    <div class="mt-2 space-y-1 text-slate-300">
                        <p>{{ old('footer_weekday_hours', $settings['footer_weekday_hours']) }}</p>
                        <p>{{ old('footer_saturday_hours', $settings['footer_saturday_hours']) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <button class="w-full rounded-lg bg-[#203749] px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">
            Guardar pie de página
        </button>
    </aside>
</form>
@endsection
