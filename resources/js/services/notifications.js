let toastrPromise = null;

const loadToastr = () => {
    toastrPromise ??= Promise.all([
        import("toastr"),
        import("toastr/build/toastr.min.css"),
    ]).then(([{ default: toastr }]) => {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            newestOnTop: true,
            positionClass: "toast-top-right",
            timeOut: 2800,
            extendedTimeOut: 1200,
            preventDuplicates: true,
        };

        return toastr;
    });

    return toastrPromise;
};

export const notify = async (type, message) => {
    const toastr = await loadToastr();
    toastr[type](message);
};
