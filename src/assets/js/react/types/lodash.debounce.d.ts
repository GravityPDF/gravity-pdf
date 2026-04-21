declare module 'lodash.debounce' {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	function debounce<T extends (...args: any[]) => any>(
		func: T,
		wait?: number,
		options?: { leading?: boolean; trailing?: boolean; maxWait?: number }
	): T & { cancel(): void; flush(): void };

	export default debounce;
}
