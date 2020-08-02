<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
    <ShortName>Ampache</ShortName>
    <Description><?php echo scrub_out(AmpConfig::get('site_title')); ?></Description>
    <Url type="text/html"
        method="get"
        template="<?php echo AmpConfig::get('web_path'); ?>/search.php">
        <Param name="s_all" value="{searchTerms}"></Param>
        <Param name="action" value="search"></Param>
    </Url>
    <img height="16" width="16">data:image/x-icon;base64,AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAQAQAAAAAAAAAAAAAAAAAAAAAAABkZGRVLi4urywsLK8rKyuvKysrrywsLK9ERESQAAAABwAAAAcxMTGQLS0trysrK68rKyuvLS0tryMjI69KSkpVNDQ0uLS0tP+5ubn/uLi4/7i4uP+7u7v/VVVV/0hISBcQEBAXb29v/8HBwf+4uLj/uLi4/76+vv+UlJT/Q0NDuC4uLq+/v7//vr6+/729vf+9vb3/wMDA/1ZWVvg+Pj4WAAAAFnR0dPjIyMj/vb29/729vf/Dw8P/mZmZ/0BAQK8wMDCvv7+//7+/v/++vr7/vr6+/8HBwf9WVlb4Pz8/FgAAABZ1dXX4ycnJ/76+vv++vr7/xMTE/5mZmf9AQECvMDAwr8DAwP/BwcH/wMDA/8DAwP/ExMT/V1dX+D8/PxYAAAAWdXV1+MvLy//AwMD/wMDA/8bGxv+bm5v/Pz8/rzExMa/Dw8P/xMTE/8PDw//Dw8P/x8fH/1hYWPg/Pz8WAAAAFnd3d/jOzs7/w8PD/8PDw//Jycn/nZ2d/z8/P68xMTGvxsbG/8fHx//Gxsb/xsbG/8rKyv9YWFj4Pz8/FgAAABZ4eHj40dHR/8bGxv/Gxsb/zc3N/5+fn/8/Pz+vMjIyr8nJyf/Kysr/ycnJ/8nJyf/Nzc3/Wlpa+D8/PxYAAAAWenp6+NTU1P/Jycn/ycnJ/9DQ0P+ioqL/Pz8/rzIyMq/Ly8v/zc3N/8vLy//Ly8v/z8/P/1tbW/hCQkITAAAAE3x8fPjX19f/y8vL/8vLy//S0tL/pKSk/z8/P68zMzOvzs7O/9DQ0P/Ozs7/zs7O/9TU1P9TU1P4GBgYHwAAAB97e3v42tra/87Ozv/Ozs7/1dXV/6ampv9AQECvNDQ0r9HR0f/T09P/0dHR/9HR0f/X19f/WFhY/h4eHukhISHpjIyM/tra2v/R0dH/0dHR/9jY2P+pqan/QEBArzQ0NK/T09P/1dXV/9PT0//T09P/1NTU/9HR0f/Nzc3/zc3N/9PT0//U1NT/09PT/9PT0//a2tr/q6ur/0BAQK82Njaw1tbW/9jY2P/W1tb/1tbW/93d3f99fX39Pz8/ukVFRbqcnJz93t7e/9bW1v/W1tb/3d3d/62trf9AQECwKioqst7e3v/g4OD/3d3d/93d3f/j4+P/h4eH/kVFRd9ISEjfpqam/uXl5f/d3d3/3d3d/+Xl5f+3t7f/SEhItFJSUlR+fn7zu7u7/7y8vP+7u7v/u7u7/729vf+9vb3/vb29/729vf+7u7v/u7u7/729vf+9vb3/Wlpa1UlJSTuNjY0TR0dHgUZGRq9HR0evRkZGr0VFRa9JSUmvTU1Nr01NTa9ISEivRUVFr0ZGRq9HR0evTk5Orw8PD1UAAAAAAYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAA==</img>
    <OutputEncoding>UTF-8</OutputEncoding>
    <InputEncoding>UTF-8</InputEncoding>
</OpenSearchDescription>
