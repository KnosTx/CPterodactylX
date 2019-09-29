import Sockette from 'sockette';
import { EventEmitter } from 'events';

export const SOCKET_EVENTS = [
    'SOCKET_OPEN',
    'SOCKET_RECONNECT',
    'SOCKET_CLOSE',
    'SOCKET_ERROR',
];

export class Websocket extends EventEmitter {
    // The socket instance being tracked.
    private socket: Sockette | null = null;

    // The URL being connected to for the socket.
    private url: string | null = null;

    // The authentication token passed along with every request to the Daemon.
    // By default this token expires every 15 minutes and must therefore be
    // refreshed at a pretty continuous interval. The socket server will respond
    // with "token expiring" and "token expired" events when approaching 3 minutes
    // and 0 minutes to expiry.
    private token: string = '';

    // Connects to the websocket instance and sets the token for the initial request.
    connect (url: string) {
        this.url = url;
        this.socket = new Sockette(`${this.url}?token=${this.token}`, {
            onmessage: e => {
                try {
                    let { event, args } = JSON.parse(e.data);
                    args ? this.emit(event, ...args) : this.emit(event);
                } catch (ex) {
                    console.warn('Failed to parse incoming websocket message.', ex);
                }
            },
            onopen: () => this.emit('SOCKET_OPEN'),
            onreconnect: () => this.emit('SOCKET_RECONNECT'),
            onclose: () => this.emit('SOCKET_CLOSE'),
            onerror: () => this.emit('SOCKET_ERROR'),
        });
    }

    // Returns the URL connected to for the socket.
    getSocketUrl (): string | null {
        return this.url;
    }

    // Sets the authentication token to use when sending commands back and forth
    // between the websocket instance.
    setToken (token: string): this {
        this.token = token;

        if (this.url) {
            this.send('auth', token);
        }

        return this;
    }

    // Returns the token being used at the current moment.
    getToken (): string {
        return this.token;
    }

    close (code?: number, reason?: string) {
        this.url = null;
        this.token = '';
        this.socket && this.socket.close(code, reason);
    }

    open () {
        this.socket && this.socket.open();
    }

    reconnect () {
        this.socket && this.socket.reconnect();
    }

    send (event: string, payload?: string | string[]) {
        this.socket && this.socket.send(JSON.stringify({
            event,
            args: Array.isArray(payload) ? payload : [ payload ],
        }));
    }
}
