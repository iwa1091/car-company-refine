import React, { useState } from 'react';
// import { Link } from '@inertiajs/react'; // Inertiaの依存関係エラーを避けるため削除
import { Menu, X } from 'lucide-react';

// ====================================================================
// エラー回避のためのダミーコンポーネントとヘルパー関数
// 実際のLaravel/Inertia環境では削除し、元のimportを使用してください
// ====================================================================

/**
 * ダミー Link コンポーネント: Inertia Linkの代わりにプレーンな <a> タグをレンダリングします
 */
const Link = ({ children, href, className, method, as, ...props }) => (
    // 'method'と'as'はデータ属性として渡し、クリック時に何も起こらないようにします（プレビュー用）
    <a 
        href={href} 
        className={className} 
        data-method={method} 
        data-as={as} 
        onClick={(e) => {
            // as="button" の場合にブラウザ遷移を防ぐため
            if (as === 'button' && method) {
                 e.preventDefault();
            }
        }}
        {...props}
    >
        {children}
    </a>
);

// ダミー route() 関数: ルート名をそのままパスとして返します (例: 'logout' -> '/logout')
// 実際のプロジェクトでは、LaravelのZiggyパッケージが提供するグローバル関数です。
const route = (name) => `/${name.replace('.', '/')}`;
// ダミー route().current() 関数: ダッシュボードの active 状態を仮で true にします
route.current = (name) => name.includes('dashboard');

// ====================================================================

/**
 * ApplicationLogo コンポーネント (インライン化)
 * @param {object} props
 */
const ApplicationLogo = (props) => {
    return (
        <svg
            {...props}
            viewBox="0 0 48 48"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path
                d="M11.395 44.436C2.571 42.748 0 31.853 0 24s2.571-18.748 11.395-20.436c3.228-.627 9.172-1.288 12.605 0 3.433 1.288 9.377 1.288 12.605 0C45.429 5.252 48 16.147 48 24s-2.571 18.748-11.395 20.436c-3.228.627-9.172 1.288-12.605 0-3.433-1.288-9.377-1.288-12.605 0z"
                fill="#6366f1"
            />
            <path
                d="M14.167 18.808l-5.657 5.657 5.657 5.657 5.657-5.657-5.657-5.657z"
                fill="#fff"
            />
            <path
                d="M24.001 24.001l-5.657 5.657 5.657 5.657 5.657-5.657-5.657-5.657z"
                fill="#fff"
            />
        </svg>
    );
};

/**
 * Dropdown コンポーネント (インライン化)
 * @param {object} props
 */
const Dropdown = ({ children }) => {
    const [open, setOpen] = useState(false);

    // ドロップダウンロジックは簡略化し、子コンポーネントを直接レンダリング
    const DropdownTrigger = ({ children, setOpen }) => (
        <div 
            onClick={() => setOpen((prev) => !prev)} 
            className="cursor-pointer select-none"
        >
            {children}
        </div>
    );

    const DropdownContent = ({ children, open, setOpen }) => (
        <div
            className={`absolute z-50 mt-2 w-48 rounded-md shadow-lg right-0 ${open ? 'block' : 'hidden'}`}
            onClick={() => setOpen(false)}
        >
            <div className="rounded-md ring-1 ring-black ring-opacity-5 bg-white">
                {children}
            </div>
        </div>
    );

    const DropdownLink = ({ href, method = 'get', as = 'a', children }) => (
        // ダミー Link コンポーネントを使用
        <Link
            href={href}
            method={method}
            as={as}
            className="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
        >
            {children}
        </Link>
    );
    
    // 子要素をマップして、props (open, setOpen) を渡すロジック
    return (
        <div className="relative">
            {React.Children.map(children, child => {
                if (child.type === Dropdown.Trigger) return React.cloneElement(child, { setOpen });
                if (child.type === Dropdown.Content) return React.cloneElement(child, { open, setOpen });
                // Dropdown.Link はそのままレンダリング
                return child;
            })}
        </div>
    );
};
Dropdown.Trigger = ({ children }) => children;
Dropdown.Content = ({ children }) => children;
// Dropdown.Link の実装をコンポーネント内に移動したため、ここではダミー定義を削除

/**
 * NavLink コンポーネント (インライン化)
 * @param {object} props
 */
const NavLink = ({ href, active, children }) => {
    const classes = active
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out';
    return (
        // ダミー Link コンポーネントを使用
        <Link href={href} className={classes}>
            {children}
        </Link>
    );
};

/**
 * ResponsiveNavLink コンポーネント (インライン化)
 * @param {object} props
 */
const ResponsiveNavLink = ({ active, children, ...props }) => {
    const classes = active
        ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-indigo-400 text-start text-base font-medium text-indigo-700 bg-indigo-50 focus:outline-none focus:text-indigo-800 focus:bg-indigo-100 focus:border-indigo-700 transition duration-150 ease-in-out'
        : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150 ease-in-out';
    return (
        // ダミー Link コンポーネントを使用
        <Link {...props} className={classes}>
            {children}
        </Link>
    );
};


// propsとして `user` を受け取ります
export default function AuthenticatedLayout({ user, header, children }) {
    const [showingNavigation, setShowingNavigation] = useState(false);

    // 重要な修正ポイント: user?.name で安全にアクセス
    const userName = user?.name || 'ゲストユーザー';

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-white border-b border-gray-100">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex">
                            <div className="shrink-0 flex items-center">
                                <Link href={route('/')}> {/* route('/') を追加 */}
                                    {/* ApplicationLogoを使用 */}
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                {/* NavLinkを使用 */}
                                {/* active={route().current('admin.dashboard')} の判定はダミー関数で処理 */}
                                <NavLink href={route('admin.dashboard')} active={route.current('admin.dashboard')}>
                                    ダッシュボード
                                </NavLink>
                                {/* 他のナビゲーションリンク... */}
                            </div>
                        </div>

                        <div className="hidden sm:flex sm:items-center sm:ms-6">
                            <div className="ms-3 relative">
                                {/* Dropdownを使用 */}
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150"
                                            >
                                                {userName}
                                                <svg
                                                    className="ms-2 -me-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        {/* Dropdown.Linkを使用 */}
                                        <Dropdown.Link href={route('profile.edit')}>プロフィール</Dropdown.Link>
                                        <Dropdown.Link href={route('logout')} method="post" as="button">
                                            ログアウト
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center sm:hidden">
                            <button
                                onClick={() => setShowingNavigation((previousState) => !previousState)}
                                className="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                            >
                                {showingNavigation ? (
                                    <X className="h-6 w-6" /> // lucide-react の X アイコン
                                ) : (
                                    <Menu className="h-6 w-6" /> // lucide-react の Menu アイコン
                                )}
                            </button>
                        </div>
                    </div>
                </div>

                <div className={(showingNavigation ? 'block' : 'hidden') + ' sm:hidden'}>
                    <div className="pt-2 pb-3 space-y-1">
                        {/* ResponsiveNavLinkを使用 */}
                        <ResponsiveNavLink href={route('admin.dashboard')} active={route.current('admin.dashboard')}>
                            ダッシュボード
                        </ResponsiveNavLink>
                    </div>

                    <div className="pt-4 pb-1 border-t border-gray-200">
                        <div className="px-4">
                            <div className="font-medium text-base text-gray-800">{userName}</div>
                            <div className="font-medium text-sm text-gray-500">{user?.email || 'メールアドレス不明'}</div>
                        </div>

                        <div className="mt-3 space-y-1">
                            {/* ResponsiveNavLinkを使用 */}
                            <ResponsiveNavLink href={route('profile.edit')}>プロフィール</ResponsiveNavLink>
                            <ResponsiveNavLink method="post" href={route('logout')} as="button">
                                ログアウト
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow">
                    <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">{header}</div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
