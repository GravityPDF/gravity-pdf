declare module 'redux-watch' {
	type WatchCallback<T> = (
		newValue: T,
		oldValue?: T,
		objectPath?: string
	) => void;
	type Watcher<T> = (fn: WatchCallback<T>) => () => void;

	function watch<T = unknown>(
		getState: () => unknown,
		objectPath: string,
		compare?: (a: T, b: T) => boolean
	): Watcher<T>;

	export default watch;
}
