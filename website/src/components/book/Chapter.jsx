export default function Chapter({ title, ...props }) {
    return <div {...props}>
        <h1 className="mt-2 text-6xl font-black tracking-tight text-slate-900 dark:text-slate-100">{title}</h1>
    </div>;
}
