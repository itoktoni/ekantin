<?php /** @var App\Models\FeeConfig $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)

                <x-input col="4" type="number" name="fee_sistem" label="Fee Sistem (Rp/transaksi)" />
                <x-input col="4" type="number" name="fee_kebersihan" label="Fee Kebersihan (Rp)" />
                <x-input col="4" type="number" name="fee_keamanan" label="Fee Keamanan (Rp)" />
                <x-input col="4" type="number" name="fee_pengelolaan" label="Fee Pengelolaan (Rp)" />
                <x-input col="4" type="number" name="fee_min_topup" label="Minimal Top Up (Rp)" />
                <x-toggle col="4" name="fee_aktif" label="Aktif" />

            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>
</x-layouts::app>
