// head ~/.local/share/gnome-shell/extensions/batt@kwynn.com/extension.js
import St from 'gi://St'; // Am I hard-linked? 12:06
import Gio from 'gi://Gio';
import { Extension } from 'resource:///org/gnome/shell/extensions/extension.js';
import * as Main from 'resource:///org/gnome/shell/ui/main.js';

export default class Battery extends Extension {
    enable() {

	this.label = new St.Label({
		    text: '',
		    style_class: 'panel-button',
		    style: 'font-size: 120%; '
		});

        Main.panel._rightBox.insert_child_at_index(this.label, 1);

        Gio.DBus.session.signal_subscribe(
            null, null, null, '/kwynn/batt/gnome/topright', null, 0,
            (c, s, p, i, sig, params) => {
                if (params?.n_children()) {
                    const v = params.get_child_value(0);
                    const txt = v.deepUnpack?.() ?? v.get_string?.()[0] ?? v;
                    this.label.text = String(txt).slice(0,40);
                }
            }
        );
    }
    disable() { this.label?.destroy(); }
}
