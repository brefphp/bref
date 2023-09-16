export default function ArticleHeader({ subTitle, date, author, authorGitHub, ...props }) {
    return <div {...props}>
        <div className="text-center text-gray-500 text-lg mb-4">
            {subTitle}
        </div>
        <figcaption className="mt-8 flex gap-x-4">
            <img src={`https://github.com/${authorGitHub}.png?size=200`}
                alt={author} className="mt-1 h-10 w-10 flex-none rounded-full bg-gray-50" />
            <div className="text-sm leading-6">
                <div className="font-semibold text-gray-900">{date}</div>
                <a href={`https://github.com/${authorGitHub}`} className="text-gray-500">{author}</a>
            </div>
        </figcaption>
    </div>;
}