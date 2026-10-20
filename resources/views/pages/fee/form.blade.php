<?php /** @var App\Models\Fee $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="4" name="code_fee" label="Kode Fee (huruf kecil, tanpa spasi)" />
                <x-input col="4" name="nama_fee" label="Nama Fee" />
                <x-select col="4" name="fee_tipe" label="Tipe Fee" :options="['persen' => 'Persen (%)', 'nominal' => 'Nominal (Rp)']" />
                <x-input col="4" type="number" step="0.01" name="value_fee" label="Nilai (%, maks 100 / Rp)" helper="Persen dari harga produk, atau nominal flat per transaksi" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
