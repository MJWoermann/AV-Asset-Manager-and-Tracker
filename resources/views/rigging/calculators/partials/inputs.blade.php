<template x-if="calculator === 'two_point_hang'">
    <div class="space-y-3 text-sm">
        <label class="block">Load W (kg)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.W" @change="recompute()"></label>
        <label class="block">Span L (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.L" @change="recompute()"></label>
        <label class="block">Distance from A, x (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.x" @change="recompute()"></label>
    </div>
</template>

<template x-if="calculator === 'lever'">
    <div class="space-y-3 text-sm">
        <fieldset class="flex flex-wrap gap-3">
            <legend class="text-xs font-semibold mb-1">Class</legend>
            <template x-for="c in ['I','II','III']" :key="c">
                <label class="inline-flex items-center gap-1">
                    <input type="radio" name="lever_class" :value="c" x-model="inputs.class" @change="recompute()">
                    <span x-text="'Class ' + c"></span>
                </label>
            </template>
        </fieldset>
        <label class="block">Load Fr (kg)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.Fr" @change="recompute()"></label>
        <label class="block">Load arm dr (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.dr" @change="recompute()"></label>
        <label class="block">Effort arm de (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.de" @change="recompute()"></label>
    </div>
</template>

<template x-if="calculator === 'bridle_two_leg'">
    <div class="space-y-3 text-sm">
        <label class="block">Load W (kg)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.W" @change="recompute()"></label>
        <label class="block">Anchor span (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.span" @change="recompute()"></label>
        <label class="block">Drop H (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.drop" @change="recompute()"></label>
        <label class="block">Offset from left (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.offset" @change="recompute()"></label>
    </div>
</template>

<template x-if="calculator === 'bridle_multi_leg'">
    <div class="space-y-3 text-sm">
        <label class="block">Load W (kg)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.W" @change="recompute()"></label>
        <label class="block">Legs<input type="number" min="2" max="16" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.legs" @change="recompute()"></label>
        <label class="block">Angle from vertical (°)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.angle_from_vertical_deg" @change="recompute()"></label>
    </div>
</template>

<template x-if="calculator === 'sling_angle_factor'">
    <div class="space-y-3 text-sm">
        <label class="block">Load W (kg)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.W" @change="recompute()"></label>
        <label class="block">Angle from vertical (°)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.angle_from_vertical_deg" @change="recompute()"></label>
        <label class="block">WLL (kg, optional)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.wll" @change="recompute()"></label>
    </div>
</template>

<template x-if="calculator === 'dead_hang'">
    <div class="space-y-3 text-sm">
        <label class="block">Load W (kg)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.W" @change="recompute()"></label>
        <label class="block">WLL (kg)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.wll" @change="recompute()"></label>
    </div>
</template>

<template x-if="calculator === 'beam_point_load'">
    <div class="space-y-3 text-sm">
        <label class="block">Span L (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.L" @change="recompute()"></label>
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="font-medium">Point loads</span>
                <button type="button" class="text-brand text-xs" @click="addBeamLoad()">+ Add</button>
            </div>
            <template x-for="(load, idx) in inputs.loads" :key="idx">
                <div class="grid grid-cols-3 gap-2">
                    <input type="number" step="any" placeholder="W kg" class="rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="load.W" @change="recompute()">
                    <input type="number" step="any" placeholder="x m" class="rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="load.x" @change="recompute()">
                    <button type="button" class="text-xs text-red-600" @click="removeBeamLoad(idx)">Remove</button>
                </div>
            </template>
        </div>
    </div>
</template>

<template x-if="calculator === 'udl_multi_point'">
    <div class="space-y-3 text-sm">
        <label class="block">Motor points N<input type="number" min="1" max="32" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.point_count" @change="onPointCountChange()"></label>
        <label class="block">Hoist self-weight each (kg)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.hoist_self_weight_kg" @change="recompute()"></label>
        <label class="block">Distribution
            <select class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="inputs.distribution_mode" @change="recompute()">
                <option value="continuous_beam">Continuous beam</option>
                <option value="equal">Equal share</option>
                <option value="custom">Custom %</option>
            </select>
        </label>
        <template x-if="inputs.distribution_mode === 'custom'">
            <div class="space-y-1">
                <p class="text-xs text-brand-charcoal dark:text-brand-silver">Percentages must sum to 100%</p>
                <template x-for="(p, idx) in inputs.custom_percents" :key="idx">
                    <label class="flex items-center gap-2">
                        <span class="w-16" x-text="'P' + (idx + 1)"></span>
                        <input type="number" step="any" class="flex-1 rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.custom_percents[idx]" @change="recompute()">
                    </label>
                </template>
            </div>
        </template>
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="font-medium">Weight lines</span>
                <button type="button" class="text-brand text-xs" @click="addWeightLine()">+ Add line</button>
            </div>
            <template x-for="(line, idx) in inputs.weight_lines" :key="idx">
                <div class="border border-brand-silver dark:border-brand-charcoal p-2 space-y-2">
                    <select class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal text-sm" x-model="line.item_type_id" @change="applyItemType(idx)">
                        <option value="">Manual weight</option>
                        <template x-for="t in itemTypes" :key="t.id">
                            <option :value="t.id" x-text="t.name + (t.rigging_weight_kg != null ? ' (' + t.rigging_weight_kg + ' kg)' : '')"></option>
                        </template>
                    </select>
                    <input type="text" placeholder="Label" class="block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="line.label" @change="recompute()">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" step="any" placeholder="Weight kg" class="rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="line.weight_kg" @change="recompute()">
                        <input type="number" step="any" placeholder="Qty" class="rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="line.quantity" @change="recompute()">
                    </div>
                    <button type="button" class="text-xs text-red-600" @click="removeWeightLine(idx)">Remove</button>
                </div>
            </template>
        </div>
    </div>
</template>

<template x-if="calculator === 'projector_lens'">
    <div class="space-y-3 text-sm">
        <label class="block">Mode
            <select class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="inputs.mode" @change="recompute()">
                <option value="width_from_ratio_distance">Ratio + distance → width</option>
                <option value="distance_from_ratio_width">Ratio + width → distance</option>
                <option value="ratio_from_distance_width">Distance + width → ratio</option>
            </select>
        </label>
        <label class="block">Aspect
            <select class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="inputs.aspect" @change="recompute()">
                <option value="16:9">16:9</option>
                <option value="16:10">16:10</option>
                <option value="4:3">4:3</option>
                <option value="custom">Custom</option>
            </select>
        </label>
        <template x-if="inputs.aspect === 'custom'">
            <label class="block">Custom aspect (W/H)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.custom_aspect" @change="recompute()"></label>
        </template>
        <label class="block" x-show="inputs.mode !== 'ratio_from_distance_width'">Throw ratio<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.throw_ratio" @change="recompute()"></label>
        <label class="block" x-show="inputs.mode !== 'distance_from_ratio_width'">Throw distance (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.throw_distance" @change="recompute()"></label>
        <label class="block" x-show="inputs.mode !== 'width_from_ratio_distance'">Image width (m)<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.image_width" @change="recompute()"></label>
        <div class="grid grid-cols-2 gap-2">
            <label class="block">Zoom min ratio<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.zoom_min" @change="recompute()"></label>
            <label class="block">Zoom max ratio<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.zoom_max" @change="recompute()"></label>
        </div>
    </div>
</template>

<template x-if="calculator === 'unit_convert'">
    <div class="space-y-3 text-sm">
        <label class="block">Value<input type="number" step="any" class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model.number="inputs.value" @change="recompute()"></label>
        <label class="block">From
            <select class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="inputs.from" @change="recompute()">
                <option value="kg">kg</option><option value="lb">lb</option>
                <option value="m">m</option><option value="ft">ft</option>
                <option value="kN">kN</option><option value="lbf">lbf</option>
            </select>
        </label>
        <label class="block">To
            <select class="mt-1 block w-full rounded border-gray-300 dark:bg-black dark:border-brand-charcoal" x-model="inputs.to" @change="recompute()">
                <option value="kg">kg</option><option value="lb">lb</option>
                <option value="m">m</option><option value="ft">ft</option>
                <option value="kN">kN</option><option value="lbf">lbf</option>
            </select>
        </label>
    </div>
</template>
