<?php /** @var App\Models\Kartu $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="6" name="kartu_barcode" />
                <x-select col="6" name="kartu_id_user" label="Siswa" :options="$siswa" />
                <x-select col="6" name="kartu_id_orangtua" label="Orang Tua" :options="$ortu" />
                <x-input col="3" name="kartu_nis" label="NIS" />
                <x-input col="3" name="kartu_kelas" label="Kelas" />
                <x-select col="3" name="kartu_status" label="Status" :options="$status" />
                <x-input col="3" type="number" name="kartu_limit_harian" label="Limit Harian (Rp)" />

                @if(isset($model) && $model->exists)
                <div class="col-span-12">
                    <p class="text-sm">Saldo saat ini: <strong>Rp{{ number_format((int) $model->kartu_saldo, 0, ',', '.') }}</strong> (berubah hanya via top up / transaksi)</p>
                </div>
                @endif

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
